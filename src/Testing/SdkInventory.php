<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

final class SdkInventory
{
    /**
     * @param array<string,mixed> $scenarios
     * @return list<array<string,mixed>>
     */
    public static function buildSdkInventory(array $scenarios, string $resolvedAuthStyle): array
    {
        $sampleApiKey = $resolvedAuthStyle === 'header'
            ? 'ak-sdk-inventory-key'
            : 'sdk-inventory-key';
        $client = Invocation::createClient(
            $resolvedAuthStyle === 'basic' ? 'sdk-inventory-user' : null,
            $sampleApiKey,
            ['guzzle' => self::createStubGuzzle()]
        );
        $methodDescriptors = Invocation::listSdkMethodDescriptors($client);
        $inventory = [];

        foreach ($methodDescriptors as $descriptor) {
            $qualifiedName = $descriptor['module'] . '.' . $descriptor['method'];
            $plan = Invocation::buildInvocationPlan(
                $descriptor['module'],
                $descriptor['method'],
                $descriptor['parameterNames'],
                $scenarios,
                'deterministic'
            );

            try {
                $captured = self::captureSdkRequest($descriptor['module'], $descriptor['method'], $plan['args'], $resolvedAuthStyle);
                $inventory[] = [
                    'module' => $descriptor['module'],
                    'method' => $descriptor['method'],
                    'qualifiedName' => $qualifiedName,
                    'parameterNames' => $descriptor['parameterNames'],
                    'endpoint' => $captured['endpoint'],
                    'normalizedEndpoint' => Normalize::normalizeSdkEndpoint(
                        $captured['endpoint'],
                        $descriptor['parameterNames'],
                        $plan['args']
                    ),
                    'domain' => $captured['domain'],
                    'requestBody' => $captured['body'],
                    'requestFields' => array_values(array_keys($captured['body'] ?? [])),
                    'requestEncoding' => $captured['encoding'],
                    'requestHeaders' => $captured['headers'],
                    'resolvedAuthStyle' => $resolvedAuthStyle,
                    'invocationArgs' => $plan['args'],
                    'notes' => [],
                ];
            } catch (\Throwable $error) {
                $inventory[] = [
                    'module' => $descriptor['module'],
                    'method' => $descriptor['method'],
                    'qualifiedName' => $qualifiedName,
                    'parameterNames' => $descriptor['parameterNames'],
                    'endpoint' => '',
                    'normalizedEndpoint' => '',
                    'domain' => str_starts_with($descriptor['module'], 'pdf.') ? 'pdf' : 'json',
                    'requestBody' => [],
                    'requestFields' => [],
                    'requestEncoding' => 'unknown',
                    'requestHeaders' => [],
                    'resolvedAuthStyle' => $resolvedAuthStyle,
                    'invocationArgs' => $plan['args'],
                    'invocationError' => $error->getMessage(),
                    'notes' => ['Unable to capture SDK request during inventory generation.'],
                ];
            }
        }

        return $inventory;
    }

    /**
     * @param list<mixed> $args
     * @return array<string,mixed>
     */
    private static function captureSdkRequest(string $moduleName, string $methodName, array $args, string $resolvedAuthStyle): array
    {
        $captured = null;
        $client = Invocation::createClient(
            $resolvedAuthStyle === 'basic' ? 'sdk-inventory-user' : null,
            $resolvedAuthStyle === 'header' ? 'ak-sdk-inventory-key' : 'sdk-inventory-key',
            [
                'guzzle' => self::createStubGuzzle(),
                'request_observer' => static function (array $snapshot) use (&$captured): void {
                    $captured = HttpCapture::redactRequest($snapshot);
                },
            ]
        );

        $method = Invocation::getClientMethod($client, $moduleName, $methodName);
        call_user_func_array($method, $args);

        if (!is_array($captured)) {
            throw new \RuntimeException(sprintf('No request captured for %s.%s', $moduleName, $methodName));
        }

        return $captured;
    }

    private static function createStubGuzzle(): GuzzleClient
    {
        $handler = static function (RequestInterface $request, array $options) {
            $host = strtolower($request->getUri()->getHost());
            $response = str_starts_with($host, 'pdf.')
                ? new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-1.4 qa')
                : new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    json_encode(['status' => true, 'generated_for' => 'sdk-introspection'], JSON_UNESCAPED_SLASHES)
                );

            return Create::promiseFor($response);
        };

        return new GuzzleClient([
            'handler' => $handler,
            'http_errors' => false,
        ]);
    }
}
