<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

use Psr\Http\Message\ResponseInterface;

final class HttpCapture
{
    /**
     * @param array<string,mixed> $snapshot
     * @return array<string,mixed>
     */
    public static function redactRequest(array $snapshot): array
    {
        $headers = [];
        foreach ((array) ($snapshot['headers'] ?? []) as $key => $value) {
            $headers[strtolower((string) $key)] = self::redactHeaderValue(
                strtolower((string) $key),
                (string) $value
            );
        }

        return [
            'url' => (string) ($snapshot['url'] ?? ''),
            'endpoint' => (string) ($snapshot['endpoint'] ?? ''),
            'normalizedEndpoint' => (string) ($snapshot['normalizedEndpoint'] ?? ($snapshot['endpoint'] ?? '')),
            'domain' => (string) ($snapshot['domain'] ?? 'json'),
            'headers' => $headers,
            'body' => (array) ($snapshot['body'] ?? []),
            'rawBody' => $snapshot['rawBody'] ?? null,
            'encoding' => (string) ($snapshot['encoding'] ?? 'unknown'),
        ];
    }

    /**
     * @param array<string,mixed> $snapshot
     * @return array<string,mixed>
     */
    public static function redactResponse(array $snapshot): array
    {
        $headers = [];
        foreach ((array) ($snapshot['headers'] ?? []) as $key => $value) {
            $headers[strtolower((string) $key)] = self::redactHeaderValue(
                strtolower((string) $key),
                (string) $value
            );
        }

        return [
            'status' => (int) ($snapshot['status'] ?? 0),
            'ok' => (bool) ($snapshot['ok'] ?? false),
            'headers' => $headers,
            'body' => $snapshot['body'] ?? null,
            'bodyType' => (string) ($snapshot['bodyType'] ?? 'unknown'),
        ];
    }

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    public static function captureManualRequest(
        string $url,
        array $headers,
        array $body,
        string $encoding
    ): array {
        $parsed = Normalize::extractEndpointFromUrl($url);

        return self::redactRequest([
            'url' => $url,
            'endpoint' => $parsed['endpoint'],
            'normalizedEndpoint' => $parsed['endpoint'],
            'domain' => $parsed['domain'],
            'headers' => $headers,
            'body' => $body,
            'rawBody' => $encoding === 'form-urlencoded'
                ? http_build_query($body)
                : json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'encoding' => $encoding,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public static function captureResponse(ResponseInterface $response, string $domain): array
    {
        $rawBody = (string) $response->getBody();
        $response->getBody()->rewind();

        $headers = [];
        foreach ($response->getHeaders() as $key => $values) {
            $headers[strtolower($key)] = implode(', ', $values);
        }

        $contentType = strtolower($headers['content-type'] ?? '');
        $snapshot = [
            'status' => $response->getStatusCode(),
            'ok' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            'headers' => $headers,
            'body' => null,
            'bodyType' => 'unknown',
        ];

        if (str_contains($contentType, 'application/json') || self::looksLikeJson($rawBody)) {
            $decoded = json_decode($rawBody, true);
            $snapshot['body'] = json_last_error() === JSON_ERROR_NONE ? $decoded : $rawBody;
            $snapshot['bodyType'] = 'json';
            return self::redactResponse($snapshot);
        }

        if (str_contains($contentType, 'application/pdf') || $domain === 'pdf') {
            $snapshot['body'] = [
                'byteLength' => strlen($rawBody),
                'type' => 'pdf',
                'isValidPdf' => self::looksLikePdf($rawBody),
            ];
            $snapshot['bodyType'] = 'pdf';
            return self::redactResponse($snapshot);
        }

        $snapshot['body'] = $rawBody;
        $snapshot['bodyType'] = $rawBody === '' ? 'unknown' : 'text';

        return self::redactResponse($snapshot);
    }

    private static function redactHeaderValue(string $key, string $value): string
    {
        if ($key === 'x-astrologyapi-key') {
            return '[REDACTED]';
        }

        if ($key === 'authorization') {
            return str_starts_with(strtolower($value), 'basic ')
                ? 'Basic [REDACTED]'
                : '[REDACTED]';
        }

        return $value;
    }

    private static function looksLikeJson(string $rawBody): bool
    {
        $trimmed = ltrim($rawBody);
        return $trimmed !== '' && in_array($trimmed[0], ['{', '['], true);
    }

    private static function looksLikePdf(string $rawBody): bool
    {
        return str_starts_with($rawBody, '%PDF');
    }
}
