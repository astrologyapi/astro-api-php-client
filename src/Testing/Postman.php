<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

final class Postman
{
    /**
     * @return list<array<string,mixed>>
     */
    public static function loadInventory(): array
    {
        $catalogPath = Paths::postmanDir() . '/postman-endpoints.json';
        if (is_file($catalogPath)) {
            $raw = file_get_contents($catalogPath);
            if ($raw !== false) {
                /** @var list<array<string,mixed>> $decoded */
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                return $decoded;
            }
        }

        return self::buildInventoryFromCollections();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function buildInventoryFromCollections(): array
    {
        $inventories = [];

        foreach (Paths::postmanCollectionFiles() as $fileName) {
            $raw = file_get_contents(Paths::postmanDir() . '/' . $fileName);
            if ($raw === false) {
                continue;
            }

            /** @var array<string,mixed> $collection */
            $collection = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            self::walkCollectionItems(
                $collection['item'] ?? [],
                function (array $item) use (&$inventories, $collection, $fileName): void {
                    $request = $item['request'] ?? null;
                    $url = $request['url']['raw'] ?? null;
                    if (!is_array($request) || !is_string($url)) {
                        return;
                    }

                    $endpoint = preg_replace('#^https?://[^/]+/v\d+/#', '', $url) ?? $url;
                    $inventories[] = [
                        'normalizedEndpoint' => Normalize::normalizePostmanEndpoint($endpoint),
                        'endpoint' => $endpoint,
                        'domain' => str_contains($url, 'https://pdf.') ? 'pdf' : 'json',
                        'method' => $request['method'] ?? 'POST',
                        'displayNames' => [$item['name'] ?? $endpoint],
                        'sourceFiles' => [$fileName],
                        'bodyMode' => $request['body']['mode'] ?? 'none',
                        'bodyParameters' => self::extractBodyParameters($request['body'] ?? []),
                        'pathParameters' => self::extractRawPathParameters($endpoint),
                        'authStyles' => self::collectAuthStyles(
                            $collection['auth']['type'] ?? null,
                            $request['auth']['type'] ?? null,
                            $request['header'] ?? []
                        ),
                        'headerKeys' => array_values(array_filter(array_map(
                            static fn (array $header): string => strtolower((string) ($header['key'] ?? '')),
                            $request['header'] ?? []
                        ))),
                    ];
                }
            );
        }

        return self::mergeDuplicateEndpoints($inventories);
    }

    /**
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public static function buildDirectRequestBody(array $entry, array $context): array
    {
        $body = [];

        foreach ($entry['bodyParameters'] ?? [] as $parameter) {
            if (($parameter['disabled'] ?? false) === true) {
                continue;
            }

            $key = (string) ($parameter['key'] ?? '');
            if ($key === '') {
                continue;
            }

            $resolved = self::resolveParameterValue($key, $entry, $context);
            if ($resolved !== null) {
                $body[$key] = $resolved;
                continue;
            }

            $fallbackValue = self::normaliseParameterValue($parameter['value'] ?? null);
            if ($fallbackValue !== null) {
                $body[$key] = $fallbackValue;
            }
        }

        return $body;
    }

    /**
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $context
     */
    public static function materialiseDirectEndpoint(array $entry, array $context): string
    {
        $mdValue = str_contains((string) $entry['endpoint'], 'sub_chardasha/:md')
            ? (string) ($context['charDashaSign'] ?? ($context['zodiacPair']['zodiac'] ?? 'aries'))
            : (string) $context['md'];

        return strtr((string) $entry['endpoint'], [
            ':chartId' => (string) $context['chartId'],
            ':planet_name' => (string) $context['planet'],
            ':planetName' => (string) $context['planet'],
            ':zodiacName' => (string) $context['zodiacPair']['zodiac'],
            ':partnerZodiacName' => (string) $context['zodiacPair']['partnerZodiac'],
            ':md' => $mdValue,
            ':ad' => (string) $context['ad'],
            ':pd' => (string) $context['pd'],
            ':sd' => (string) $context['sd'],
        ]);
    }

    /**
     * @param list<string> $authStyles
     */
    public static function authSummary(array $authStyles): string
    {
        return $authStyles === [] ? 'unspecified in collection' : implode(', ', $authStyles);
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    private static function walkCollectionItems(array $items, callable $visit): void
    {
        foreach ($items as $item) {
            if (isset($item['item']) && is_array($item['item'])) {
                self::walkCollectionItems($item['item'], $visit);
                continue;
            }

            $visit($item);
        }
    }

    /**
     * @param list<array<string,mixed>> $entries
     * @return list<array<string,mixed>>
     */
    private static function mergeDuplicateEndpoints(array $entries): array
    {
        $merged = [];

        foreach ($entries as $entry) {
            $key = (string) $entry['normalizedEndpoint'];
            if (!isset($merged[$key])) {
                $merged[$key] = $entry;
                continue;
            }

            $merged[$key]['displayNames'] = array_values(array_unique(array_merge(
                $merged[$key]['displayNames'],
                $entry['displayNames']
            )));
            $merged[$key]['sourceFiles'] = array_values(array_unique(array_merge(
                $merged[$key]['sourceFiles'],
                $entry['sourceFiles']
            )));
            $merged[$key]['authStyles'] = array_values(array_unique(array_merge(
                $merged[$key]['authStyles'],
                $entry['authStyles']
            )));
            $merged[$key]['headerKeys'] = array_values(array_unique(array_merge(
                $merged[$key]['headerKeys'],
                $entry['headerKeys']
            )));
            $merged[$key]['pathParameters'] = array_values(array_unique(array_merge(
                $merged[$key]['pathParameters'],
                $entry['pathParameters']
            )));
            $merged[$key]['bodyParameters'] = self::mergeParameters(
                $merged[$key]['bodyParameters'],
                $entry['bodyParameters']
            );
        }

        $values = array_values($merged);
        usort(
            $values,
            static fn (array $left, array $right): int => $left['normalizedEndpoint'] <=> $right['normalizedEndpoint']
        );

        return $values;
    }

    /**
     * @param list<array<string,mixed>> $current
     * @param list<array<string,mixed>> $incoming
     * @return list<array<string,mixed>>
     */
    private static function mergeParameters(array $current, array $incoming): array
    {
        $merged = [];

        foreach (array_merge($current, $incoming) as $parameter) {
            $key = (string) ($parameter['key'] ?? '');
            if ($key === '') {
                continue;
            }

            if (!isset($merged[$key])) {
                $merged[$key] = $parameter;
                continue;
            }

            if (($merged[$key]['value'] ?? null) === null && ($parameter['value'] ?? null) !== null) {
                $merged[$key]['value'] = $parameter['value'];
            }

            $merged[$key]['disabled'] = ($merged[$key]['disabled'] ?? false) && ($parameter['disabled'] ?? false);
        }

        return array_values($merged);
    }

    /**
     * @param array<string,mixed> $body
     * @return list<array<string,mixed>>
     */
    private static function extractBodyParameters(array $body): array
    {
        $mode = (string) ($body['mode'] ?? 'none');

        if ($mode === 'urlencoded') {
            return array_map(
                static fn (array $parameter): array => [
                    'key' => (string) ($parameter['key'] ?? ''),
                    'value' => $parameter['value'] ?? null,
                    'disabled' => (bool) ($parameter['disabled'] ?? false),
                ],
                $body['urlencoded'] ?? []
            );
        }

        if ($mode === 'raw' && is_string($body['raw'] ?? null)) {
            $decoded = json_decode($body['raw'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            $parameters = [];
            foreach ($decoded as $key => $value) {
                $parameters[] = [
                    'key' => (string) $key,
                    'value' => $value,
                    'disabled' => false,
                ];
            }

            return $parameters;
        }

        return [];
    }

    /**
     * @param list<array<string,mixed>> $headers
     * @return list<string>
     */
    private static function collectAuthStyles(?string $collectionAuthType, ?string $itemAuthType, array $headers): array
    {
        $authStyles = [];

        if ($collectionAuthType === 'basic' || $itemAuthType === 'basic') {
            $authStyles[] = 'basic';
        }

        foreach ($headers as $header) {
            if (strtolower((string) ($header['key'] ?? '')) === 'x-astrologyapi-key') {
                $authStyles[] = 'header';
            }
        }

        return array_values(array_unique($authStyles));
    }

    /**
     * @return list<string>
     */
    private static function extractRawPathParameters(string $endpoint): array
    {
        preg_match_all('/:[^\/]+/', $endpoint, $matches);
        return $matches[0] ?? [];
    }

    private static function normaliseParameterValue(mixed $value): mixed
    {
        if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || is_array($value)) {
            return $value;
        }

        return null;
    }

    /**
     * @param array<string,mixed> $entry
     * @param array<string,mixed> $context
     */
    private static function resolveParameterValue(string $key, array $entry, array $context): mixed
    {
        if (preg_match('/^([mfps])_(day|month|year|hour|min|minute|lat|latitude|lon|longitude|tzone|timezone)$/', $key, $matches) === 1) {
            $source = match ($matches[1]) {
                'm' => $context['match']['male'],
                'f' => $context['match']['female'],
                'p' => $context['couple']['person1'],
                's' => $context['couple']['person2'],
            };

            return self::mapBirthField($source, $matches[2]);
        }

        return match ($key) {
            'day', 'month', 'year', 'hour', 'min', 'lat', 'lon', 'tzone' => $context['birth'][$key] ?? null,
            'minute' => $context['birth']['min'] ?? null,
            'latitude' => $context['birth']['lat'] ?? null,
            'longitude' => $context['birth']['lon'] ?? null,
            'timezone' => $context['birth']['tzone'] ?? null,
            'place' => $context['placeQuery'],
            'maxRows' => $context['maxRows'],
            'name' => ($entry['domain'] ?? 'json') === 'pdf' ? $context['subjectName'] : ($context['numerology']['name'] ?? null),
            'full name', 'full_name' => $context['numerology']['name'] ?? $context['subjectName'],
            'partner_name' => $context['partnerName'],
            'm_first_name' => self::splitFullName($context['subjectName'])['first_name'] ?? null,
            'm_last_name' => self::splitFullName($context['subjectName'])['last_name'] ?? null,
            'f_first_name' => self::splitFullName($context['partnerName'])['first_name'] ?? null,
            'f_last_name' => self::splitFullName($context['partnerName'])['last_name'] ?? null,
            'p_first_name' => self::splitFullName($context['subjectName'])['first_name'] ?? null,
            'p_last_name' => self::splitFullName($context['subjectName'])['last_name'] ?? null,
            's_first_name' => self::splitFullName($context['partnerName'])['first_name'] ?? null,
            's_last_name' => self::splitFullName($context['partnerName'])['last_name'] ?? null,
            'm_place', 'f_place', 'p_place', 's_place' => $context['subjectPlace'],
            'gender' => $context['birth']['gender'] ?? 'male',
            'language' => 'en',
            'chart_style' => $context['branding']['chart_style'] ?? 'NORTH_INDIAN',
            'footer_link' => $context['branding']['footer_link'] ?? null,
            'logo_url' => $context['branding']['logo_url'] ?? null,
            'company_name' => $context['branding']['company_name'] ?? null,
            'company_info' => $context['branding']['company_info'] ?? null,
            'domain_url' => $context['branding']['domain_url'] ?? null,
            'company_email' => $context['branding']['company_email'] ?? null,
            'company_landline' => $context['branding']['company_landline'] ?? null,
            'company_mobile' => $context['branding']['company_mobile'] ?? null,
            'ashtakoot', 'papasyam', 'dashakoot' => 'true',
            'varshaphal_year' => $context['varshaphalYear'],
            'solar_year' => $context['solarYear'],
            'date' => $context['numerology']['day'] ?? ($context['birth']['day'] ?? null),
            'ayanamsha' => 'LAHIRI',
            'year_count' => 1,
            'tarot_id' => $context['tarotId'],
            default => null,
        };
    }

    /**
     * @param array<string,mixed> $source
     */
    private static function mapBirthField(array $source, string $field): mixed
    {
        return match ($field) {
            'day', 'month', 'year', 'hour', 'min', 'lat', 'lon', 'tzone' => $source[$field] ?? null,
            'minute' => $source['min'] ?? null,
            'latitude' => $source['lat'] ?? null,
            'longitude' => $source['lon'] ?? null,
            'timezone' => $source['tzone'] ?? null,
            default => null,
        };
    }

    /**
     * @return array{first_name?: string, last_name?: string}
     */
    private static function splitFullName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, static fn ($value) => $value !== ''));

        if ($parts === []) {
            return [];
        }

        if (count($parts) === 1) {
            return ['first_name' => $parts[0]];
        }

        return [
            'first_name' => $parts[0],
            'last_name' => implode(' ', array_slice($parts, 1)),
        ];
    }
}
