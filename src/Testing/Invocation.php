<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

use AstrologyAPI\Client;
use AstrologyAPI\Models\BirthData;
use AstrologyAPI\Models\NumeroData;
use AstrologyAPI\Models\PDFBranding;

final class Invocation
{
    /**
     * @return array<int,array{module: string, resolver: callable}>
     */
    private static function moduleDescriptors(): array
    {
        return [
            ['module' => 'vedic', 'resolver' => static fn (Client $client) => $client->vedic()],
            ['module' => 'kp', 'resolver' => static fn (Client $client) => $client->kp()],
            ['module' => 'lalKitab', 'resolver' => static fn (Client $client) => $client->lalKitab()],
            ['module' => 'horoscopes', 'resolver' => static fn (Client $client) => $client->horoscopes()],
            ['module' => 'numerology', 'resolver' => static fn (Client $client) => $client->numerology()],
            ['module' => 'western', 'resolver' => static fn (Client $client) => $client->western()],
            ['module' => 'westernTransit', 'resolver' => static fn (Client $client) => $client->westernTransit()],
            ['module' => 'tarot', 'resolver' => static fn (Client $client) => $client->tarot()],
            ['module' => 'chinese', 'resolver' => static fn (Client $client) => $client->chinese()],
            ['module' => 'pdf.vedic', 'resolver' => static fn (Client $client) => $client->pdf()->vedic],
            ['module' => 'pdf.western', 'resolver' => static fn (Client $client) => $client->pdf()->western],
            ['module' => 'location', 'resolver' => static fn (Client $client) => $client->location()],
        ];
    }

    /**
     * @param array<string,mixed> $options
     */
    public static function createClient(?string $userId, string $apiKey, array $options = []): Client
    {
        if ($userId !== null && $userId !== '') {
            $options['user_id'] ??= $userId;
        }

        return new Client($apiKey, $options);
    }

    /**
     * @return list<array{module: string, method: string, parameterNames: list<string>}>
     */
    public static function listSdkMethodDescriptors(Client $client): array
    {
        $methods = [];

        foreach (self::moduleDescriptors() as $descriptor) {
            $target = ($descriptor['resolver'])($client);
            $reflection = new \ReflectionClass($target);

            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->isConstructor()) {
                    continue;
                }

                if (!str_starts_with($method->getName(), 'get')) {
                    continue;
                }

                $methods[] = [
                    'module' => $descriptor['module'],
                    'method' => $method->getName(),
                    'parameterNames' => array_map(
                        static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
                        $method->getParameters()
                    ),
                ];
            }
        }

        usort(
            $methods,
            static fn (array $left, array $right): int => [$left['module'], $left['method']] <=> [$right['module'], $right['method']]
        );

        return $methods;
    }

    public static function getClientMethod(Client $client, string $moduleName, string $methodName): callable
    {
        foreach (self::moduleDescriptors() as $descriptor) {
            if ($descriptor['module'] !== $moduleName) {
                continue;
            }

            $target = ($descriptor['resolver'])($client);
            if (!is_callable([$target, $methodName])) {
                break;
            }

            return [$target, $methodName];
        }

        throw new \RuntimeException(sprintf('Method %s.%s is not callable.', $moduleName, $methodName));
    }

    /**
     * @param list<string> $parameterNames
     * @param array<string,mixed> $scenarios
     * @return array{args: list<mixed>, context: array<string,mixed>}
     */
    public static function buildInvocationPlan(
        string $moduleName,
        string $methodName,
        array $parameterNames,
        array $scenarios,
        string $strategy = 'deterministic'
    ): array {
        $context = self::buildInvocationContext($moduleName, $methodName, $scenarios, $strategy);
        $args = [];

        foreach ($parameterNames as $parameterName) {
            $args[] = self::resolveParameterValue($parameterName, $moduleName, $methodName, $context);
        }

        while ($args !== [] && end($args) === null) {
            array_pop($args);
        }

        return [
            'args' => $args,
            'context' => $context,
        ];
    }

    /**
     * @param array<string,mixed> $scenarios
     * @return array<string,mixed>
     */
    private static function buildInvocationContext(
        string $moduleName,
        string $methodName,
        array $scenarios,
        string $strategy
    ): array {
        $birthPool = [
            $scenarios['birthStandard'],
            $scenarios['birthNegativeTimezone'],
            $scenarios['birthFractionalTimezone'],
            $scenarios['birthDstSensitive'],
            $scenarios['birthEdgeDate'],
        ];

        $baseBirth = $strategy === 'random'
            ? $birthPool[array_rand($birthPool)]
            : self::selectDeterministicBirth($moduleName, $methodName, $scenarios);
        $zodiacAlternates = array_merge(
            [$scenarios['zodiacPairs']['primary']],
            $scenarios['zodiacPairs']['alternates']
        );

        $zodiacPair = $strategy === 'random'
            ? $zodiacAlternates[array_rand($zodiacAlternates)]
            : $scenarios['zodiacPairs']['primary'];
        $numerologyPool = [$scenarios['numerologyBasic'], $scenarios['numerologyAlternate']];
        $numerology = $strategy === 'random'
            ? $numerologyPool[array_rand($numerologyPool)]
            : $scenarios['numerologyBasic'];

        return [
            'birth' => $baseBirth,
            'match' => $scenarios['matchBasic'],
            'couple' => $scenarios['coupleBasic'],
            'numerology' => $numerology,
            'branding' => $scenarios['pdfBranding'],
            'zodiacPair' => $zodiacPair,
            'charDashaSign' => $zodiacPair['zodiac'],
            'planet' => $strategy === 'random' ? self::randomFrom(['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn']) : 'sun',
            'chartId' => $strategy === 'random' ? self::randomFrom(['D1', 'D9', 'D10']) : 'D1',
            'md' => $strategy === 'random' ? self::randomFrom(['sun', 'moon', 'mars', 'rahu', 'jupiter', 'saturn', 'mercury', 'ketu', 'venus']) : 'sun',
            'ad' => $strategy === 'random' ? self::randomFrom(['sun', 'moon', 'mars', 'rahu', 'jupiter', 'saturn', 'mercury', 'ketu', 'venus']) : 'moon',
            'pd' => $strategy === 'random' ? self::randomFrom(['sun', 'moon', 'mars', 'rahu', 'jupiter', 'saturn', 'mercury', 'ketu', 'venus']) : 'mars',
            'sd' => $strategy === 'random' ? self::randomFrom(['sun', 'moon', 'mars', 'rahu', 'jupiter', 'saturn', 'mercury', 'ketu', 'venus']) : 'venus',
            'placeQuery' => $baseBirth['place'] ?? 'Mumbai',
            'maxRows' => 6,
            'subjectName' => $baseBirth['name'] ?? 'QA Test Subject',
            'partnerName' => $scenarios['coupleBasic']['person2']['name'] ?? 'QA Partner',
            'subjectPlace' => $baseBirth['place'] ?? 'Mumbai, Maharashtra, India',
            'tarotId' => 5,
            'varshaphalYear' => ($baseBirth['year'] ?? 1990) + 36,
            'solarYear' => ($baseBirth['year'] ?? 1990) + 36,
        ];
    }

    /**
     * @param array<string,mixed> $context
     */
    private static function resolveParameterValue(string $parameterName, string $moduleName, string $methodName, array $context): mixed
    {
        return match ($parameterName) {
            'data' => self::resolveDataArgument($moduleName, $methodName, $context),
            'male' => self::buildBirthData($context['match']['male']),
            'female' => self::buildBirthData($context['match']['female']),
            'person1' => self::buildBirthData($context['couple']['person1']),
            'person2' => self::buildBirthData($context['couple']['person2']),
            'planetName', 'planet' => $context['planet'],
            'zodiacName', 'zodiac' => $context['zodiacPair']['zodiac'],
            'partnerZodiacName', 'partnerZodiac' => $context['zodiacPair']['partnerZodiac'],
            'chartId' => $context['chartId'],
            'md' => ($moduleName === 'vedic' && $methodName === 'getSubCharDasha')
                ? ($context['charDashaSign'] ?? $context['zodiacPair']['zodiac'])
                : $context['md'],
            'ad' => $context['ad'],
            'pd' => $context['pd'],
            'sd' => $context['sd'],
            'maxRows' => $context['maxRows'],
            'placeName' => $context['placeQuery'],
            'branding' => self::buildBranding($context['branding']),
            'extra' => self::resolveExtraArgument($moduleName, $methodName, $context),
            'gender' => $context['birth']['gender'] ?? 'male',
            'aspects' => true,
            'ayanamsha' => 'LAHIRI',
            'varshaphalYear' => $context['varshaphalYear'],
            'solarYear' => $context['solarYear'],
            'tarotId' => $context['tarotId'],
            'timezone' => $context['birth']['tzone'],
            default => null,
        };
    }

    /**
     * @param array<string,mixed> $context
     */
    private static function resolveDataArgument(string $moduleName, string $methodName, array $context): mixed
    {
        if ($moduleName === 'numerology' && !in_array($methodName, [
            'getTable',
            'getReport',
            'getFavTime',
            'getPlaceVastu',
            'getFastsReport',
            'getFavLord',
            'getFavMantra',
            'getDailyPrediction',
        ], true)) {
            return self::buildNumeroData($context['numerology']);
        }

        return self::buildBirthData($context['birth']);
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private static function resolveExtraArgument(string $moduleName, string $methodName, array $context): array
    {
        if ($moduleName === 'pdf.vedic' && $methodName === 'getMatchMaking') {
            return [
                'name' => $context['subjectName'],
                'partner_name' => $context['partnerName'],
                'place' => $context['subjectPlace'],
                'language' => 'en',
            ];
        }

        if ($moduleName === 'pdf.vedic') {
            return [
                'gender' => $context['birth']['gender'] ?? 'male',
                'place' => $context['subjectPlace'],
                'language' => 'en',
            ];
        }

        if ($moduleName === 'pdf.western' && $methodName === 'getSynastry') {
            return [
                'name' => $context['subjectName'],
                'partner_name' => $context['partnerName'],
                'place' => $context['subjectPlace'],
                'language' => 'en',
            ];
        }

        if ($moduleName === 'pdf.western') {
            $extra = [
                'name' => $context['subjectName'],
                'place' => $context['subjectPlace'],
                'language' => 'en',
            ];

            if ($methodName === 'getSolarReturn') {
                $extra['solar_year'] = $context['solarYear'];
            }

            return $extra;
        }

        return [];
    }

    /**
     * @param array<string,mixed> $scenario
     */
    private static function buildBirthData(array $scenario): BirthData
    {
        return new BirthData(
            day: (int) $scenario['day'],
            month: (int) $scenario['month'],
            year: (int) $scenario['year'],
            hour: (int) $scenario['hour'],
            min: (int) $scenario['min'],
            lat: (float) $scenario['lat'],
            lon: (float) $scenario['lon'],
            tzone: (float) $scenario['tzone'],
        );
    }

    /**
     * @param array<string,mixed> $scenario
     */
    private static function buildNumeroData(array $scenario): NumeroData
    {
        return new NumeroData(
            day: (int) $scenario['day'],
            month: (int) $scenario['month'],
            year: (int) $scenario['year'],
            name: (string) ($scenario['name'] ?? '')
        );
    }

    /**
     * @param array<string,mixed> $scenario
     */
    private static function buildBranding(array $scenario): PDFBranding
    {
        return new PDFBranding(
            logoUrl: $scenario['logo_url'] ?? null,
            companyName: $scenario['company_name'] ?? null,
            companyInfo: $scenario['company_info'] ?? null,
            domainUrl: $scenario['domain_url'] ?? null,
            companyEmail: $scenario['company_email'] ?? null,
            companyLandline: $scenario['company_landline'] ?? null,
            companyMobile: $scenario['company_mobile'] ?? null,
            footerLink: $scenario['footer_link'] ?? null,
            chartStyle: $scenario['chart_style'] ?? null,
        );
    }

    /**
     * @param array<string,mixed> $scenarios
     * @return array<string,mixed>
     */
    private static function selectDeterministicBirth(string $moduleName, string $methodName, array $scenarios): array
    {
        if ($moduleName === 'location' && $methodName === 'getTimezone') {
            return $scenarios['birthDstSensitive'];
        }

        if ($moduleName === 'westernTransit') {
            return $scenarios['birthNegativeTimezone'];
        }

        if (str_starts_with($moduleName, 'pdf.')) {
            return $scenarios['birthFractionalTimezone'];
        }

        if (str_contains($methodName, 'Panchang') || str_contains($methodName, 'Festival')) {
            return $scenarios['birthEdgeDate'];
        }

        return $scenarios['birthStandard'];
    }

    /**
     * @param list<string> $pool
     */
    private static function randomFrom(array $pool): string
    {
        return $pool[array_rand($pool)];
    }
}
