<?php

declare(strict_types=1);

namespace AstrologyAPI\Tests;

use AstrologyAPI\Client;
use AstrologyAPI\Models\BirthData;
use AstrologyAPI\Models\NumeroData;
use AstrologyAPI\Models\PDFBranding;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class NamespaceParityTest extends TestCase
{
    /**
     * @param list<Response> $responses
     * @param array<int,array<string,mixed>> $history
     */
    private function createClient(array $responses, array &$history): Client
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($history));

        return new Client('ak-test-key', [
            'guzzle' => new GuzzleClient([
                'handler' => $handler,
                'http_errors' => false,
            ]),
            'user_id' => 'user-123',
        ]);
    }

    private function makeBirthData(): BirthData
    {
        return new BirthData(
            day: 10,
            month: 5,
            year: 1990,
            hour: 19,
            min: 55,
            lat: 19.2,
            lon: 72.83,
            tzone: 5.5,
        );
    }

    private function makeNumeroData(): NumeroData
    {
        return new NumeroData(
            day: 10,
            month: 5,
            year: 1990,
            name: 'Aarav Mehta',
        );
    }

    public function testHoroscopeNextUsesDailyPathAndTimezoneBody(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->horoscopes()->getNext('aries', 5.5);

        $request = $history[0]['request'];
        $this->assertStringContainsString('/sun_sign_prediction/daily/next/aries', (string) $request->getUri());
        $this->assertStringContainsString('"timezone":5.5', (string) $request->getBody());
    }

    public function testExtendedChartDefaultsToD1AndAddsAyanamsha(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->vedic()->getExtendedChart($this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/horo_chart_extended/D1', (string) $request->getUri());
        $this->assertStringContainsString('"ayanamsha":"LAHIRI"', (string) $request->getBody());
    }

    public function testSubVdashaUsesPathParameterAndAyanamsha(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->vedic()->getSubVdasha('sun', $this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/sub_vdasha/sun', (string) $request->getUri());
        $this->assertStringContainsString('"ayanamsha":"LAHIRI"', (string) $request->getBody());
    }

    public function testSubCharDashaUsesFormUrlencodedBodyAndAyanamsha(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->vedic()->getSubCharDasha('aries', $this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/sub_chardasha/aries', (string) $request->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('ayanamsha=LAHIRI', (string) $request->getBody());
    }

    public function testChineseHoroscopeUsesChineseZodiacEndpoint(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->chinese()->getChineseHoroscope($this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/chinese_zodiac', (string) $request->getUri());
    }

    public function testCurrentCharDashaSubUsesLiveCurrentCharDashaEndpoint(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->vedic()->getCurrentCharDashaSub($this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/current_chardasha', (string) $request->getUri());
        $this->assertStringNotContainsString('/current_chardasha/sub', (string) $request->getUri());
    }

    public function testCurrentYoginiDashaSubUsesLiveCurrentYoginiDashaEndpoint(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->vedic()->getCurrentYoginiDashaSub($this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/current_yogini_dasha', (string) $request->getUri());
        $this->assertStringNotContainsString('/current_yogini_dasha/sub', (string) $request->getUri());
    }

    public function testPersonalCharacteristicsUsesGeneralAscendantReportParityEndpoint(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->vedic()->getPersonalCharacteristics($this->makeBirthData(), 'male');

        $request = $history[0]['request'];
        $this->assertStringContainsString('/general_ascendant_report', (string) $request->getUri());
        $body = (string) $request->getBody();
        $this->assertStringContainsString('"gender":"male"', $body);
        $this->assertStringContainsString('"ayanamsha":"LAHIRI"', $body);
    }

    public function testLegacyWesternTransitMethodsUseLiveTransitEndpoints(): void
    {
        $history = [];
        $client = $this->createClient(
            [
                new Response(200, ['Content-Type' => 'application/json'], '{"status":true}'),
                new Response(200, ['Content-Type' => 'application/json'], '{"status":true}'),
                new Response(200, ['Content-Type' => 'application/json'], '{"status":true}'),
                new Response(200, ['Content-Type' => 'application/json'], '{"status":true}'),
                new Response(200, ['Content-Type' => 'application/json'], '{"status":true}'),
            ],
            $history
        );

        $data = $this->makeBirthData();
        $client->westernTransit()->getTransit($data);
        $client->westernTransit()->getTransitExtended($data);
        $client->westernTransit()->getTransitAspects($data);
        $client->westernTransit()->getTransitForecast($data);
        $client->westernTransit()->getTransitReport($data);

        $this->assertStringContainsString('/tropical_transits/daily', (string) $history[0]['request']->getUri());
        $this->assertStringContainsString('/tropical_transits/weekly', (string) $history[1]['request']->getUri());
        $this->assertStringContainsString('/natal_transits/daily', (string) $history[2]['request']->getUri());
        $this->assertStringContainsString('/tropical_transits/monthly', (string) $history[3]['request']->getUri());
        $this->assertStringContainsString('/natal_transits/weekly', (string) $history[4]['request']->getUri());
    }

    public function testWesternAspectsUsesWesternHoroscopeEndpoint(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->western()->getAspects($this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/western_horoscope', (string) $request->getUri());
    }

    public function testWesternSunSignUsesSunGeneralSignReportEndpoint(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->western()->getSunSign($this->makeBirthData());

        $request = $history[0]['request'];
        $this->assertStringContainsString('/general_sign_report/tropical/sun', (string) $request->getUri());
    }

    public function testGeoDetailsUsesMaxRowsParameter(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->location()->getGeoDetails('Mumbai', 6);

        $request = $history[0]['request'];
        $this->assertStringContainsString('"maxRows":6', (string) $request->getBody());
        $this->assertStringNotContainsString('max_rows', (string) $request->getBody());
    }

    public function testFriendshipReportUsesCouplePayload(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $person1 = $this->makeBirthData();
        $person2 = new BirthData(
            day: 4,
            month: 11,
            year: 1988,
            hour: 6,
            min: 20,
            lat: 40.7128,
            lon: -74.006,
            tzone: -5.0,
        );

        $client->western()->getFriendshipReport($person1, $person2);

        $request = $history[0]['request'];
        $this->assertStringContainsString('/friendship_report/tropical', (string) $request->getUri());
        $body = (string) $request->getBody();
        $this->assertStringContainsString('"p_day":10', $body);
        $this->assertStringContainsString('"s_day":4', $body);
    }

    public function testWesternPdfUsesFormUrlencodedContract(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/pdf'], '%PDF-1.4 test')],
            $history
        );

        $client->pdf()->western->getNatalChart(
            $this->makeBirthData(),
            new PDFBranding(companyName: 'QA Co'),
            [
                'name' => 'Aarav Mehta',
                'place' => 'Mumbai, Maharashtra, India',
                'language' => 'en',
            ]
        );

        $request = $history[0]['request'];
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $body = (string) $request->getBody();
        $this->assertStringContainsString('minute=55', $body);
        $this->assertStringContainsString('latitude=19.2', $body);
        $this->assertStringContainsString('timezone=5.5', $body);
        $this->assertStringContainsString('company_name=QA+Co', $body);
    }

    public function testWesternNumerologyUsesMirroredNameFieldsAndFormUrlencodedBody(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '{"status":true}')],
            $history
        );

        $client->numerology()->getNumerologicalNumbers($this->makeNumeroData());

        $request = $history[0]['request'];
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));
        $body = (string) $request->getBody();
        $this->assertStringContainsString('name=Aarav+Mehta', $body);
        $this->assertStringContainsString('full_name=Aarav+Mehta', $body);
        $this->assertStringContainsString('full+name=Aarav+Mehta', $body);
        $this->assertStringContainsString('date=10', $body);
    }

    public function testVarshaphalMunthaAllowsScalarJsonResponses(): void
    {
        $history = [];
        $client = $this->createClient(
            [new Response(200, ['Content-Type' => 'application/json'], '"Scorpio"')],
            $history
        );

        $result = $client->vedic()->getVarshaphalMuntha($this->makeBirthData(), 2026);

        $request = $history[0]['request'];
        $this->assertStringContainsString('/varshaphal_muntha', (string) $request->getUri());
        $this->assertSame('Scorpio', $result);
    }
}
