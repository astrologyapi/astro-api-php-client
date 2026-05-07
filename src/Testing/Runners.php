<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

use AstrologyAPI\Errors\AstrologyAPIException;
use AstrologyAPI\Errors\PlanRestrictedError;
use AstrologyAPI\Errors\QuotaExceededError;
use GuzzleHttp\Client as GuzzleClient;

final class Runners
{
    /**
     * @param array<string,mixed> $entry
     * @param array{userId: string|null, apiKey: string} $credentials
     * @param array{args: list<mixed>, context: array<string,mixed>} $plan
     * @return array<string,mixed>
     */
    public static function executeSdkMethod(array $entry, array $credentials, array $plan): array
    {
        $resolvedAuthStyle = Env::inferResolvedAuthStyle($credentials['apiKey']);
        $sdkRequest = null;
        $sdkResponse = null;

        $client = Invocation::createClient(
            $credentials['userId'],
            $credentials['apiKey'],
            [
                'request_observer' => static function (array $snapshot) use (&$sdkRequest): void {
                    $sdkRequest = HttpCapture::redactRequest($snapshot);
                },
                'response_observer' => static function (array $snapshot) use (&$sdkResponse): void {
                    $sdkResponse = HttpCapture::redactResponse($snapshot);
                },
            ]
        );

        try {
            $method = Invocation::getClientMethod($client, (string) $entry['module'], (string) $entry['method']);
            $result = call_user_func_array($method, $plan['args']);

            if (is_array($sdkRequest)) {
                $sdkRequest['normalizedEndpoint'] = Normalize::normalizeSdkEndpoint(
                    (string) $sdkRequest['endpoint'],
                    $entry['parameterNames'],
                    $plan['args']
                );
            }

            return [
                'module' => $entry['module'],
                'method' => $entry['method'],
                'qualifiedName' => $entry['qualifiedName'],
                'normalizedEndpoint' => $entry['normalizedEndpoint'],
                'status' => 'success',
                'resolvedAuthStyle' => $resolvedAuthStyle,
                'invocationArgs' => $plan['args'],
                'sdkRequest' => $sdkRequest,
                'sdkResponse' => $sdkResponse,
                'resultSnapshot' => Normalize::serialiseResult($result, $sdkResponse),
                'responseShape' => Normalize::inferShape(Normalize::serialiseResult($result, $sdkResponse)),
            ];
        } catch (\Throwable $error) {
            if (is_array($sdkRequest)) {
                $sdkRequest['normalizedEndpoint'] = Normalize::normalizeSdkEndpoint(
                    (string) $sdkRequest['endpoint'],
                    $entry['parameterNames'],
                    $plan['args']
                );
            }

            $errorStatus = self::extractErrorStatus($error);
            $status = Normalize::isNotTestableStatus($errorStatus)
                || $error instanceof QuotaExceededError
                || $error instanceof PlanRestrictedError
                ? 'not-testable'
                : 'failure';

            return [
                'module' => $entry['module'],
                'method' => $entry['method'],
                'qualifiedName' => $entry['qualifiedName'],
                'normalizedEndpoint' => $entry['normalizedEndpoint'],
                'status' => $status,
                'resolvedAuthStyle' => $resolvedAuthStyle,
                'invocationArgs' => $plan['args'],
                'sdkRequest' => $sdkRequest,
                'sdkResponse' => $sdkResponse,
                'errorName' => $error::class,
                'errorMessage' => $error->getMessage(),
                'errorStatus' => $errorStatus,
            ];
        }
    }

    /**
     * @param array<string,mixed> $postmanEntry
     * @param array{userId: string|null, apiKey: string} $credentials
     * @param array{args: list<mixed>, context: array<string,mixed>} $plan
     * @return array<string,mixed>
     */
    public static function executeDirectRequest(
        array $postmanEntry,
        string $normalizedEndpoint,
        array $credentials,
        array $plan
    ): array {
        $resolvedAuthStyle = Env::inferResolvedAuthStyle($credentials['apiKey']);
        $endpoint = Postman::materialiseDirectEndpoint($postmanEntry, $plan['context']);
        $body = Postman::buildDirectRequestBody($postmanEntry, $plan['context']);
        $encoding = ($postmanEntry['bodyMode'] ?? 'urlencoded') === 'urlencoded'
            ? 'form-urlencoded'
            : 'json';

        return self::executeManualDirectRequest(
            $endpoint,
            $normalizedEndpoint,
            (string) ($postmanEntry['domain'] ?? 'json'),
            $body,
            $encoding,
            $credentials,
            $resolvedAuthStyle
        );
    }

    /**
     * @param array<string,mixed> $bodySnapshot
     * @param array{userId: string|null, apiKey: string} $credentials
     * @return array<string,mixed>
     */
    public static function executeDirectRequestFromResolvedEndpoint(
        string $endpoint,
        string $normalizedEndpoint,
        string $domain,
        array $bodySnapshot,
        string $encoding,
        array $credentials
    ): array {
        return self::executeManualDirectRequest(
            $endpoint,
            $normalizedEndpoint,
            $domain,
            $bodySnapshot,
            $encoding,
            $credentials,
            Env::inferResolvedAuthStyle($credentials['apiKey'])
        );
    }

    /**
     * @param array<string,mixed> $sdkResult
     * @param array<string,mixed>|null $directResult
     * @return array<string,mixed>
     */
    public static function compareExecutionResults(array $sdkResult, ?array $directResult): array
    {
        if ($directResult === null) {
            return [
                'module' => $sdkResult['module'],
                'method' => $sdkResult['method'],
                'qualifiedName' => $sdkResult['qualifiedName'],
                'normalizedEndpoint' => $sdkResult['normalizedEndpoint'],
                'resolvedAuthStyle' => $sdkResult['resolvedAuthStyle'],
                'consistent' => false,
                'sdk' => $sdkResult,
                'diffLines' => ['Direct API request could not be prepared for this endpoint.'],
                'notes' => [],
            ];
        }

        if (
            ($sdkResult['status'] ?? null) === 'not-testable' ||
            ($directResult['status'] ?? null) === 'not-testable'
        ) {
            return [
                'module' => $sdkResult['module'],
                'method' => $sdkResult['method'],
                'qualifiedName' => $sdkResult['qualifiedName'],
                'normalizedEndpoint' => $sdkResult['normalizedEndpoint'],
                'resolvedAuthStyle' => $sdkResult['resolvedAuthStyle'],
                'consistent' => false,
                'skippedReason' => 'Endpoint is not testable with the current plan/credentials.',
                'sdk' => $sdkResult,
                'direct' => $directResult,
                'diffLines' => [],
                'notes' => [],
            ];
        }

        if (($sdkResult['sdkRequest']['domain'] ?? null) === 'pdf') {
            $sdkPdfOk = ($sdkResult['status'] ?? null) === 'success'
                && self::isSuccessfulPdfResponse($sdkResult['sdkResponse'] ?? null);
            $directPdfOk = ($directResult['status'] ?? null) === 'success'
                && self::isSuccessfulPdfResponse($directResult['response'] ?? null);

            return [
                'module' => $sdkResult['module'],
                'method' => $sdkResult['method'],
                'qualifiedName' => $sdkResult['qualifiedName'],
                'normalizedEndpoint' => $sdkResult['normalizedEndpoint'],
                'resolvedAuthStyle' => $sdkResult['resolvedAuthStyle'],
                'consistent' => $sdkPdfOk && $directPdfOk,
                'sdk' => $sdkResult,
                'direct' => $directResult,
                'diffLines' => $sdkPdfOk && $directPdfOk
                    ? []
                    : ['PDF validation failed for either the SDK response or the direct API response.'],
                'notes' => [
                    'PDF endpoints are validated by either a non-empty binary PDF response or a successful JSON response containing a pdf_url.',
                ],
            ];
        }

        $sdkComparable = Normalize::sortDeep($sdkResult['resultSnapshot'] ?? ($sdkResult['sdkResponse']['body'] ?? null));
        $directComparable = Normalize::sortDeep($directResult['resultSnapshot'] ?? ($directResult['response']['body'] ?? null));
        $diffLines = Normalize::diffValues($sdkComparable, $directComparable);

        return [
            'module' => $sdkResult['module'],
            'method' => $sdkResult['method'],
            'qualifiedName' => $sdkResult['qualifiedName'],
            'normalizedEndpoint' => $sdkResult['normalizedEndpoint'],
            'resolvedAuthStyle' => $sdkResult['resolvedAuthStyle'],
            'consistent' => ($sdkResult['status'] ?? null) === ($directResult['status'] ?? null) && $diffLines === [],
            'sdk' => $sdkResult,
            'direct' => $directResult,
            'diffLines' => $diffLines,
            'notes' => [],
        ];
    }

    /**
     * @param array<string,mixed> $directResult
     * @param array<string,mixed> $sdkResult
     */
    public static function shouldRetryWithSdkFallback(array $directResult, array $sdkResult): bool
    {
        if (($directResult['status'] ?? null) !== 'failure' || ($sdkResult['status'] ?? null) !== 'success') {
            return false;
        }

        return self::findSdkOnlyValidationFields($directResult, $sdkResult) !== [];
    }

    /**
     * @param array<string,mixed> $sdkResult
     * @param array<string,mixed> $directResult
     * @param array<string,mixed> $comparison
     */
    public static function shouldRetryComparisonWithSdkFallback(array $sdkResult, array $directResult, array $comparison): bool
    {
        if (!isset($sdkResult['sdkRequest']) || !is_array($sdkResult['sdkRequest'])) {
            return false;
        }

        $drift = self::findBodyShapeDrift($sdkResult['sdkRequest'], $directResult['request'] ?? null);
        if (($drift['missingFromDirect'] ?? []) === [] && ($drift['extraInDirect'] ?? []) === []) {
            return false;
        }

        return ($directResult['status'] ?? null) === 'failure' || !($comparison['consistent'] ?? false);
    }

    /**
     * @param array<string,mixed>|null $sdkRequest
     * @param array<string,mixed>|null $directRequest
     * @return array{missingFromDirect: list<string>, extraInDirect: list<string>}
     */
    public static function findBodyShapeDrift(?array $sdkRequest, ?array $directRequest): array
    {
        $sdkFields = array_values(array_unique(array_keys((array) ($sdkRequest['body'] ?? []))));
        $directFields = array_values(array_unique(array_keys((array) ($directRequest['body'] ?? []))));
        sort($sdkFields);
        sort($directFields);

        return [
            'missingFromDirect' => array_values(array_diff($sdkFields, $directFields)),
            'extraInDirect' => array_values(array_diff($directFields, $sdkFields)),
        ];
    }

    /**
     * @param array<string,mixed> $directResult
     * @param array<string,mixed> $sdkResult
     * @return list<string>
     */
    public static function findSdkOnlyValidationFields(array $directResult, array $sdkResult): array
    {
        $validationFields = self::extractValidationFieldKeys($directResult['response']['body'] ?? null);
        if ($validationFields === []) {
            return [];
        }

        $sdkBody = (array) ($sdkResult['sdkRequest']['body'] ?? []);
        $directBody = (array) ($directResult['request']['body'] ?? []);

        return array_values(array_filter(
            $validationFields,
            static fn (string $field): bool => array_key_exists($field, $sdkBody) && !array_key_exists($field, $directBody)
        ));
    }

    /**
     * @return list<string>
     */
    public static function extractValidationFieldKeys(mixed $body): array
    {
        if (!is_array($body) || !isset($body['error']) || !is_array($body['error'])) {
            return [];
        }

        $keys = [];
        foreach ($body['error'] as $entry) {
            if (!is_array($entry) || !isset($entry['context']['key']) || !is_string($entry['context']['key'])) {
                continue;
            }

            $keys[] = $entry['context']['key'];
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param array<string,mixed> $body
     * @param array{userId: string|null, apiKey: string} $credentials
     * @return array<string,mixed>
     */
    private static function executeManualDirectRequest(
        string $endpoint,
        string $normalizedEndpoint,
        string $domain,
        array $body,
        string $encoding,
        array $credentials,
        string $resolvedAuthStyle
    ): array {
        $baseUrl = $domain === 'pdf'
            ? 'https://pdf.astrologyapi.com/v1'
            : 'https://json.astrologyapi.com/v1';
        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        $headers = self::buildDirectHeaders($credentials, $resolvedAuthStyle);
        $client = new GuzzleClient(['http_errors' => false]);

        if ($encoding === 'form-urlencoded') {
            $headers['content-type'] = 'application/x-www-form-urlencoded';
            $request = HttpCapture::captureManualRequest($url, $headers, $body, 'form-urlencoded');
            $request['normalizedEndpoint'] = $normalizedEndpoint;
            $options = ['headers' => $headers, 'form_params' => $body];
        } else {
            $headers['content-type'] = 'application/json';
            $request = HttpCapture::captureManualRequest($url, $headers, $body, 'json');
            $request['normalizedEndpoint'] = $normalizedEndpoint;
            $options = ['headers' => $headers, 'json' => $body];
        }

        try {
            $response = $client->request('POST', $url, $options);
            $responseSnapshot = HttpCapture::captureResponse($response, $domain);
            $status = Normalize::isNotTestableStatus($response->getStatusCode())
                ? 'not-testable'
                : ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 'success' : 'failure');

            return [
                'normalizedEndpoint' => $normalizedEndpoint,
                'resolvedAuthStyle' => $resolvedAuthStyle,
                'status' => $status,
                'request' => $request,
                'response' => $responseSnapshot,
                'resultSnapshot' => $responseSnapshot['body'] ?? null,
                'errorStatus' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? null : $response->getStatusCode(),
                'errorMessage' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300
                    ? null
                    : (is_string($responseSnapshot['body'] ?? null)
                        ? $responseSnapshot['body']
                        : json_encode($responseSnapshot['body'] ?? null, JSON_UNESCAPED_SLASHES)),
            ];
        } catch (\Throwable $error) {
            return [
                'normalizedEndpoint' => $normalizedEndpoint,
                'resolvedAuthStyle' => $resolvedAuthStyle,
                'status' => 'failure',
                'request' => $request,
                'errorName' => $error::class,
                'errorMessage' => $error->getMessage(),
            ];
        }
    }

    /**
     * @param array{userId: string|null, apiKey: string} $credentials
     * @return array<string,string>
     */
    private static function buildDirectHeaders(array $credentials, string $resolvedAuthStyle): array
    {
        if ($resolvedAuthStyle === 'basic') {
            return [
                'authorization' => 'Basic ' . base64_encode((string) $credentials['userId'] . ':' . $credentials['apiKey']),
            ];
        }

        return [
            'x-astrologyapi-key' => $credentials['apiKey'],
        ];
    }

    private static function extractErrorStatus(\Throwable $error): ?int
    {
        if ($error instanceof AstrologyAPIException) {
            return $error->getHttpCode();
        }

        $code = $error->getCode();
        return is_int($code) && $code > 0 ? $code : null;
    }

    /**
     * @param array<string,mixed>|null $response
     */
    private static function isSuccessfulPdfResponse(?array $response): bool
    {
        if ($response === null) {
            return false;
        }

        if (($response['bodyType'] ?? null) === 'pdf') {
            return isset($response['body']['byteLength'])
                && (int) $response['body']['byteLength'] > 0
                && (($response['body']['isValidPdf'] ?? false) === true);
        }

        if (($response['bodyType'] ?? null) === 'json' && is_array($response['body'] ?? null)) {
            return ($response['body']['status'] ?? null) === true
                && is_string($response['body']['pdf_url'] ?? null)
                && $response['body']['pdf_url'] !== '';
        }

        return false;
    }
}
