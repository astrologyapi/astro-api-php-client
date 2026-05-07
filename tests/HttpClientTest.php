<?php

declare(strict_types=1);

namespace AstrologyAPI\Tests;

use AstrologyAPI\Errors\AstrologyAPIException;
use AstrologyAPI\Http\HttpClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    /**
     * @param list<Response> $responses
     * @param array<string,mixed> $options
     * @param array<int,array<string,mixed>> $history
     */
    private function createHttpClient(array $responses, string $apiKey, array $options, array &$history): HttpClient
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));

        $guzzle = new GuzzleClient([
            'handler' => $handler,
            'http_errors' => false,
        ]);

        return new HttpClient($guzzle, $apiKey, $options);
    }

    public function testHeaderAuthUsesAstrologyApiKeyHeader(): void
    {
        $history = [];
        $client = $this->createHttpClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            'ak-live-key',
            [],
            $history
        );

        $client->post('birth_details', ['day' => 10]);

        $request = $history[0]['request'];
        $this->assertSame('ak-live-key', $request->getHeaderLine('x-astrologyapi-key'));
        $this->assertSame('', $request->getHeaderLine('Authorization'));
    }

    public function testBasicAuthUsesUserIdAndApiKey(): void
    {
        $history = [];
        $client = $this->createHttpClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            'basic-key',
            ['user_id' => 'user-123'],
            $history
        );

        $client->post('birth_details', ['day' => 10]);

        $request = $history[0]['request'];
        $this->assertSame('Basic ' . base64_encode('user-123:basic-key'), $request->getHeaderLine('Authorization'));
        $this->assertSame('', $request->getHeaderLine('x-astrologyapi-key'));
    }

    public function testFormUrlencodedRequestUsesFormParamsAndCaptureObserver(): void
    {
        $history = [];
        $captured = null;
        $client = $this->createHttpClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            'ak-form-key',
            [
                'request_observer' => static function (array $snapshot) use (&$captured): void {
                    $captured = $snapshot;
                },
            ],
            $history
        );

        $client->post(
            'lalkitab_horoscope',
            ['day' => 10, 'ayanamsha' => 'LAHIRI'],
            ['encoding' => 'form-urlencoded']
        );

        $request = $history[0]['request'];
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('day=10', (string) $request->getBody());
        $this->assertStringContainsString('ayanamsha=LAHIRI', (string) $request->getBody());
        $this->assertIsArray($captured);
        $this->assertSame('form-urlencoded', $captured['encoding']);
    }

    public function testArrayErrorPayloadIsNormalizedIntoAstrologyApiExceptionMessage(): void
    {
        $history = [];
        $client = $this->createHttpClient(
            [
                new Response(
                    405,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'status' => false,
                        'msg' => 'Request data validation failed. Kindly check your request data again.',
                        'error' => [
                            [
                                'message' => '"day" must be a number',
                                'path' => ['day'],
                            ],
                        ],
                    ], JSON_THROW_ON_ERROR)
                ),
            ],
            'ak-error-key',
            [],
            $history
        );

        try {
            $client->post('legacy_validation_endpoint', ['day' => null]);
            $this->fail('Expected AstrologyAPIException to be thrown.');
        } catch (AstrologyAPIException $error) {
            $this->assertSame(405, $error->getHttpCode());
            $this->assertSame(
                'Request data validation failed. Kindly check your request data again. ("day" must be a number)',
                $error->getMessage()
            );
        }
    }

    public function testNestedErrorArrayWithoutTopLevelMessageStillProducesStringMessage(): void
    {
        $history = [];
        $client = $this->createHttpClient(
            [
                new Response(
                    429,
                    ['Content-Type' => 'application/json'],
                    json_encode([
                        'error' => [
                            ['message' => 'Rate limit exceeded'],
                            ['message' => 'Retry later'],
                        ],
                    ], JSON_THROW_ON_ERROR)
                ),
            ],
            'ak-rate-limit-key',
            [],
            $history
        );

        try {
            $client->post('birth_details', ['day' => 10]);
            $this->fail('Expected AstrologyAPIException to be thrown.');
        } catch (AstrologyAPIException $error) {
            $this->assertSame('Rate limit exceeded; Retry later', $error->getMessage());
        }
    }

    public function testScalarJsonSuccessResponseIsReturnedWithoutTypeError(): void
    {
        $history = [];
        $client = $this->createHttpClient(
            [
                new Response(
                    200,
                    ['Content-Type' => 'application/json'],
                    '"Scorpio"'
                ),
            ],
            'ak-scalar-key',
            [],
            $history
        );

        $result = $client->post('varshaphal_muntha', ['day' => 10]);

        $this->assertSame('Scorpio', $result);
    }
}
