<?php

declare(strict_types=1);

namespace AstrologyAPI\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use AstrologyAPI\Errors\AstrologyAPIException;
use AstrologyAPI\Errors\AuthenticationError;
use AstrologyAPI\Errors\PlanRestrictedError;
use AstrologyAPI\Errors\QuotaExceededError;
use AstrologyAPI\Errors\RateLimitError;
use AstrologyAPI\Errors\ServerError;
use AstrologyAPI\Errors\EndpointNotFoundError;
use AstrologyAPI\Errors\ValidationError;
use RuntimeException;

/**
 * Internal HTTP client for AstrologyAPI requests.
 *
 * Requests support both JSON and form-urlencoded bodies. Authentication is
 * inferred from the API key:
 * - API keys containing "ak-" use x-astrologyapi-key
 * - all other API keys use Authorization: Basic base64(userId:apiKey)
 *
 * Supported domains:
 *   "json" → https://json.astrologyapi.com/v1/{endpoint}
 *   "pdf"  → https://pdf.astrologyapi.com/v1/{endpoint}  (returns raw binary)
 */
class HttpClient
{
    private const BASE_JSON_URL = 'https://json.astrologyapi.com/v1/';
    private const BASE_PDF_URL  = 'https://pdf.astrologyapi.com/v1/';

    /** @var callable|null */
    private $requestObserver;

    /** @var callable|null */
    private $responseObserver;

    public function __construct(
        private ClientInterface $guzzle,
        private string $apiKey,
        private array $options = []
    ) {
        $this->requestObserver = $options['request_observer'] ?? null;
        $this->responseObserver = $options['response_observer'] ?? null;
    }

    // ── Public methods ────────────────────────────────────────────────────────

    /**
     * POST to a JSON endpoint and return the decoded response.
     *
     * @param  array<string,mixed> $body
     * @param  array{
     *     encoding?: 'json'|'form-urlencoded',
     *     language?: string,
     *     domain?: 'json'|'pdf'
     * } $requestOptions
     * @return mixed
     * @throws AstrologyAPIException|RuntimeException
     */
    public function post(string $endpoint, array $body = [], array $requestOptions = []): mixed
    {
        $response = $this->request($endpoint, $body, $requestOptions['domain'] ?? 'json', $requestOptions);
        $rawBody = $this->readAndResetBody($response);
        $snapshot = $this->captureResponseSnapshot($response, $requestOptions['domain'] ?? 'json', $rawBody);
        $this->notifyResponseObserver($snapshot);

        return $this->decodeJson($response, $rawBody);
    }

    /**
     * POST to a PDF endpoint and return the raw binary string.
     *
     * @param  array<string,mixed> $body
     * @param  array{
     *     encoding?: 'json'|'form-urlencoded',
     *     language?: string,
     *     domain?: 'json'|'pdf'
     * } $requestOptions
     * @throws AstrologyAPIException|RuntimeException
     */
    public function postBinary(string $endpoint, array $body = [], array $requestOptions = []): string
    {
        $response = $this->request($endpoint, $body, $requestOptions['domain'] ?? 'pdf', $requestOptions);
        $rawBody = $this->readAndResetBody($response);
        $snapshot = $this->captureResponseSnapshot($response, $requestOptions['domain'] ?? 'pdf', $rawBody);
        $this->notifyResponseObserver($snapshot);

        $decodedBody = $snapshot['bodyType'] === 'json' && is_array($snapshot['body'])
            ? $snapshot['body']
            : null;

        $this->assertSuccess($response, $decodedBody);
        return $rawBody;
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Execute the HTTP POST with exponential-backoff retry on transient errors.
     *
     * @param  array<string,mixed> $body
     * @param  array{
     *     encoding?: 'json'|'form-urlencoded',
     *     language?: string
     * } $requestOptions
     * @throws GuzzleException
     */
    private function request(string $endpoint, array $body, string $domain, array $requestOptions = []): ResponseInterface
    {
        $baseUrl     = $domain === 'pdf'
            ? (string) ($this->options['pdf_base_url'] ?? self::BASE_PDF_URL)
            : (string) ($this->options['base_url'] ?? self::BASE_JSON_URL);
        $url         = $baseUrl . $endpoint;
        $maxRetries  = (int) ($this->options['retries'] ?? 3);
        $attempt     = 0;
        $encoding    = $requestOptions['encoding'] ?? 'json';
        $headers     = $this->buildHeaders($encoding, $requestOptions['language'] ?? null);
        $payload     = $this->buildPayload($body, $encoding);

        while (true) {
            try {
                $this->notifyRequestObserver([
                    'url' => $url,
                    'endpoint' => trim($endpoint, '/'),
                    'domain' => $domain,
                    'headers' => $headers,
                    'body' => $body,
                    'rawBody' => $this->serialiseBody($body, $encoding),
                    'encoding' => $encoding,
                ]);

                return $this->guzzle->request('POST', $url, [
                    'headers' => $headers,
                    'timeout' => $this->options['timeout'] ?? 30.0,
                    'http_errors' => false,
                ] + $payload);
            } catch (GuzzleException $e) {
                $attempt++;
                if ($attempt > $maxRetries) {
                    throw $e;
                }
                usleep((int) (2 ** $attempt * 100_000)); // exponential back-off: 0.2s, 0.4s, 0.8s …
            }
        }
    }

    /**
     * JSON-decode the response and throw typed errors for non-2xx status codes.
     *
     * @return mixed
     */
    private function decodeJson(ResponseInterface $response, string $rawBody): mixed
    {
        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Failed to decode JSON response: ' . json_last_error_msg()
            );
        }

        $this->assertSuccess($response, $data);

        return $data;
    }

    /**
     * Throw a typed exception for any non-2xx HTTP status code.
     *
     * @param mixed $data Decoded payload for error messages, or null for binary responses.
     * @throws AstrologyAPIException
     */
    private function assertSuccess(ResponseInterface $response, mixed $data): void
    {
        $status  = $response->getStatusCode();
        $message = $this->extractErrorMessage($data) ?? $response->getReasonPhrase();

        match (true) {
            $status === 400,
            $status === 422 => throw new ValidationError($message, $status),
            $status === 401 => throw new AuthenticationError(
                'Authentication failed. Check your AstrologyAPI credentials.',
                $status
            ),
            $status === 402 => throw new QuotaExceededError(
                'API quota exceeded. Please upgrade your plan at astrologyapi.com.',
                $status
            ),
            $status === 403 => throw new PlanRestrictedError(
                'This endpoint is not available on your current plan.',
                $status
            ),
            $status === 404 => throw new EndpointNotFoundError($message, $status),
            $status === 429 => throw new RateLimitError($message, $status),
            $status >= 500  => throw new ServerError(
                "AstrologyAPI server error ({$status}). Please try again later.",
                $status
            ),
            $status >= 400  => throw new AstrologyAPIException($message, $status),
            default         => null,
        };
    }

    /**
     * @param mixed $data
     */
    private function extractErrorMessage(mixed $data): ?string
    {
        if (!is_array($data)) {
            return null;
        }

        $primary = null;
        foreach (['message', 'error_msg', 'msg'] as $key) {
            $primary = $this->stringifyErrorMessage($data[$key] ?? null);
            if ($primary !== null) {
                break;
            }
        }

        $detail = null;
        foreach (['error', 'errors'] as $key) {
            $detail = $this->stringifyErrorMessage($data[$key] ?? null);
            if ($detail !== null) {
                break;
            }
        }

        if ($primary !== null && $detail !== null && !str_contains($primary, $detail)) {
            return sprintf('%s (%s)', $primary, $detail);
        }

        return $primary ?? $detail;
    }

    private function stringifyErrorMessage(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (!is_array($value)) {
            return null;
        }

        if (array_key_exists('message', $value)) {
            return $this->stringifyErrorMessage($value['message']);
        }

        $messages = [];
        foreach ($value as $item) {
            $message = $this->stringifyErrorMessage($item);
            if ($message === null || in_array($message, $messages, true)) {
                continue;
            }

            $messages[] = $message;
            if (count($messages) >= 3) {
                break;
            }
        }

        if ($messages !== []) {
            return implode('; ', $messages);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return null;
        }

        $trimmed = trim($encoded);
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<string,string>
     */
    private function buildHeaders(string $encoding, ?string $language): array
    {
        $headers = [
            'Content-Type' => $encoding === 'form-urlencoded'
                ? 'application/x-www-form-urlencoded'
                : 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'astrologyapi-php/2.0.0',
        ];

        if ($language !== null && $language !== '') {
            $headers['Accept-Language'] = $language;
        }

        if ($this->shouldUseHeaderAuth($this->apiKey)) {
            $headers['x-astrologyapi-key'] = $this->apiKey;
            return $headers;
        }

        $userId = trim((string) ($this->options['user_id'] ?? ''));
        if ($userId === '') {
            throw new RuntimeException('The "user_id" option is required when using Basic Authorization credentials.');
        }

        $headers['Authorization'] = 'Basic ' . base64_encode($userId . ':' . $this->apiKey);
        return $headers;
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function buildPayload(array $body, string $encoding): array
    {
        if ($encoding === 'form-urlencoded') {
            return ['form_params' => $body];
        }

        return ['json' => $body];
    }

    /**
     * @param array<string,mixed> $body
     */
    private function serialiseBody(array $body, string $encoding): string
    {
        if ($encoding === 'form-urlencoded') {
            return http_build_query($body);
        }

        return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    private function shouldUseHeaderAuth(string $apiKey): bool
    {
        return str_contains($apiKey, 'ak-');
    }

    private function readAndResetBody(ResponseInterface $response): string
    {
        $stream = $response->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $rawBody = $stream->getContents();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return $rawBody;
    }

    /**
     * @return array{
     *     status: int,
     *     ok: bool,
     *     headers: array<string,string>,
     *     body: mixed,
     *     bodyType: 'json'|'text'|'pdf'|'unknown'
     * }
     */
    private function captureResponseSnapshot(ResponseInterface $response, string $domain, string $rawBody): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $key => $values) {
            $headers[strtolower($key)] = implode(', ', $values);
        }

        $contentType = strtolower($headers['content-type'] ?? '');
        $status = $response->getStatusCode();
        $ok = $status >= 200 && $status < 300;

        if (str_contains($contentType, 'application/json') || $this->looksLikeJson($rawBody)) {
            $decoded = json_decode($rawBody, true);
            return [
                'status' => $status,
                'ok' => $ok,
                'headers' => $headers,
                'body' => json_last_error() === JSON_ERROR_NONE ? $decoded : $rawBody,
                'bodyType' => 'json',
            ];
        }

        if (str_contains($contentType, 'application/pdf') || $domain === 'pdf') {
            return [
                'status' => $status,
                'ok' => $ok,
                'headers' => $headers,
                'body' => [
                    'byteLength' => strlen($rawBody),
                    'type' => 'pdf',
                    'isValidPdf' => $this->looksLikePdf($rawBody),
                ],
                'bodyType' => 'pdf',
            ];
        }

        return [
            'status' => $status,
            'ok' => $ok,
            'headers' => $headers,
            'body' => $rawBody,
            'bodyType' => $rawBody === '' ? 'unknown' : 'text',
        ];
    }

    /**
     * @param array<string,mixed> $snapshot
     */
    private function notifyRequestObserver(array $snapshot): void
    {
        if (is_callable($this->requestObserver)) {
            ($this->requestObserver)($snapshot);
        }
    }

    /**
     * @param array<string,mixed> $snapshot
     */
    private function notifyResponseObserver(array $snapshot): void
    {
        if (is_callable($this->responseObserver)) {
            ($this->responseObserver)($snapshot);
        }
    }

    private function looksLikeJson(string $rawBody): bool
    {
        $trimmed = ltrim($rawBody);
        return $trimmed !== '' && in_array($trimmed[0], ['{', '['], true);
    }

    private function looksLikePdf(string $rawBody): bool
    {
        return str_starts_with($rawBody, '%PDF');
    }
}
