<?php

declare(strict_types=1);

namespace AstrologyAPI\Tests;

use AstrologyAPI\Testing\Docs;
use AstrologyAPI\Testing\Invocation;
use AstrologyAPI\Testing\Postman;
use AstrologyAPI\Testing\Runners;
use AstrologyAPI\Testing\TestData;
use PHPUnit\Framework\TestCase;

final class TestingSupportTest extends TestCase
{
    public function testPostmanDirectBodyPreservesScalarFallbackValues(): void
    {
        $body = Postman::buildDirectRequestBody(
            [
                'domain' => 'json',
                'bodyParameters' => [
                    ['key' => 'custom_flag', 'value' => true, 'disabled' => false],
                    ['key' => 'custom_label', 'value' => 'sunrise', 'disabled' => false],
                ],
            ],
            []
        );

        $this->assertSame(true, $body['custom_flag']);
        $this->assertSame('sunrise', $body['custom_label']);
    }

    public function testPostmanDirectBodyUsesNumerologyNameForFullNameAndDate(): void
    {
        $body = Postman::buildDirectRequestBody(
            [
                'domain' => 'json',
                'bodyParameters' => [
                    ['key' => 'full name', 'value' => null, 'disabled' => false],
                    ['key' => 'date', 'value' => null, 'disabled' => false],
                ],
            ],
            [
                'numerology' => [
                    'day' => 10,
                    'name' => 'Aarav Mehta',
                ],
                'subjectName' => 'Different Subject',
            ]
        );

        $this->assertSame('Aarav Mehta', $body['full name']);
        $this->assertSame(10, $body['date']);
    }

    public function testSubCharDashaDirectEndpointUsesSignInsteadOfPlanetSeed(): void
    {
        $endpoint = Postman::materialiseDirectEndpoint(
            ['endpoint' => 'sub_chardasha/:md'],
            [
                'md' => 'sun',
                'charDashaSign' => 'aries',
                'zodiacPair' => ['zodiac' => 'taurus', 'partnerZodiac' => 'virgo'],
                'chartId' => 'D1',
                'planet' => 'sun',
                'ad' => 'moon',
                'pd' => 'mars',
                'sd' => 'venus',
            ]
        );

        $this->assertSame('sub_chardasha/aries', $endpoint);
    }

    public function testCompareFallbackTriggersWhenPostmanBodyDriftsAndComparisonMismatches(): void
    {
        $sdkResult = [
            'status' => 'success',
            'sdkRequest' => [
                'body' => [
                    'day' => 10,
                    'varshaphal_year' => 2026,
                ],
            ],
        ];
        $directResult = [
            'status' => 'success',
            'request' => [
                'body' => [
                    'day' => 10,
                ],
            ],
        ];
        $comparison = ['consistent' => false];

        $this->assertTrue(Runners::shouldRetryComparisonWithSdkFallback($sdkResult, $directResult, $comparison));
    }

    public function testInvalidPdfBinaryIsNotAcceptedAsSuccessfulPdfComparison(): void
    {
        $sdkResult = [
            'module' => 'pdf.western',
            'method' => 'getNatalChart',
            'qualifiedName' => 'pdf.western.getNatalChart',
            'normalizedEndpoint' => 'natal_horoscope_report/tropical',
            'resolvedAuthStyle' => 'header',
            'status' => 'success',
            'sdkRequest' => ['domain' => 'pdf'],
            'sdkResponse' => [
                'bodyType' => 'pdf',
                'body' => [
                    'byteLength' => 24,
                    'type' => 'pdf',
                    'isValidPdf' => false,
                ],
            ],
        ];
        $directResult = [
            'status' => 'success',
            'response' => [
                'bodyType' => 'pdf',
                'body' => [
                    'byteLength' => 24,
                    'type' => 'pdf',
                    'isValidPdf' => true,
                ],
            ],
        ];

        $comparison = Runners::compareExecutionResults($sdkResult, $directResult);

        $this->assertFalse($comparison['consistent']);
        $this->assertSame(
            ['PDF validation failed for either the SDK response or the direct API response.'],
            $comparison['diffLines']
        );
    }

    public function testSdkModulesDocumentUsesRealPhpAccessorSyntax(): void
    {
        $document = Docs::buildSdkModulesDocument(
            [
                [
                    'module' => 'vedic',
                    'method' => 'getBirthDetails',
                    'qualifiedName' => 'vedic.getBirthDetails',
                    'endpoint' => 'birth_details',
                    'normalizedEndpoint' => 'birth_details',
                    'domain' => 'json',
                    'resolvedAuthStyle' => 'header',
                    'parameterNames' => ['data'],
                    'requestEncoding' => 'json',
                    'requestFields' => ['day', 'month'],
                    'requestBody' => ['day' => 10, 'month' => 5],
                ],
                [
                    'module' => 'pdf.western',
                    'method' => 'getNatalChart',
                    'qualifiedName' => 'pdf.western.getNatalChart',
                    'endpoint' => 'natal_horoscope_report/tropical',
                    'normalizedEndpoint' => 'natal_horoscope_report/tropical',
                    'domain' => 'pdf',
                    'resolvedAuthStyle' => 'header',
                    'parameterNames' => ['data', 'branding', 'extra'],
                    'requestEncoding' => 'form-urlencoded',
                    'requestFields' => ['day', 'month'],
                    'requestBody' => ['day' => 10, 'month' => 5],
                ],
            ],
            [],
            [],
            'header',
            '2026-04-23T00:00:00+00:00'
        );

        $this->assertStringContainsString('client->vedic()->getBirthDetails(...)', $document);
        $this->assertStringContainsString('client->pdf()->western->getNatalChart(...)', $document);
    }

    public function testInvocationPlanProvidesKpAspectsFlag(): void
    {
        $plan = Invocation::buildInvocationPlan(
            'kp',
            'getHoroscope',
            ['data', 'aspects', 'ayanamsha'],
            TestData::loadTestScenarios()
        );

        $this->assertCount(3, $plan['args']);
        $this->assertTrue($plan['args'][1]);
        $this->assertSame('LAHIRI', $plan['args'][2]);
    }

    public function testInvocationPlanProvidesLocationPlaceName(): void
    {
        $plan = Invocation::buildInvocationPlan(
            'location',
            'getGeoDetails',
            ['placeName', 'maxRows'],
            TestData::loadTestScenarios()
        );

        $this->assertCount(2, $plan['args']);
        $this->assertSame('Mumbai, Maharashtra, India', $plan['args'][0]);
        $this->assertSame(6, $plan['args'][1]);
    }

    public function testSdkMethodDescriptorsExcludeRetiredLegacyApis(): void
    {
        $client = Invocation::createClient(null, 'ak-test-key');
        $descriptors = Invocation::listSdkMethodDescriptors($client);
        $qualifiedNames = array_map(
            static fn (array $descriptor): string => $descriptor['module'] . '.' . $descriptor['method'],
            $descriptors
        );

        $this->assertNotContains('horoscopes.getYearly', $qualifiedNames);
        $this->assertNotContains('vedic.getChalitChart', $qualifiedNames);
        $this->assertNotContains('vedic.getPanchadaMaitri', $qualifiedNames);
        $this->assertNotContains('western.getDignities', $qualifiedNames);
        $this->assertNotContains('western.getExtendedPlanets', $qualifiedNames);
        $this->assertNotContains('chinese.getChineseCompatibility', $qualifiedNames);
    }
}
