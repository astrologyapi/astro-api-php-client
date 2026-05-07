<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

final class Normalize
{
    /**
     * @return array{endpoint: string, domain: string}
     */
    public static function extractEndpointFromUrl(string $rawUrl): array
    {
        $parts = parse_url($rawUrl);
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $endpoint = preg_replace('#^v\d+/#', '', $path) ?? $path;
        $host = strtolower((string) ($parts['host'] ?? ''));

        return [
            'endpoint' => $endpoint,
            'domain' => str_starts_with($host, 'pdf.') ? 'pdf' : 'json',
        ];
    }

    public static function normalizePostmanEndpoint(string $endpoint): string
    {
        $normalized = preg_replace('#^https?://[^/]+/v\d+/#', '', $endpoint) ?? $endpoint;
        $replacements = [
            ':chartId' => '<chart>',
            ':planet_name' => '<planet>',
            ':planetName' => '<planet>',
            ':zodiacName' => '<zodiac>',
            ':partnerZodiacName' => '<zodiac2>',
            ':md' => '<md>',
            ':ad' => '<ad>',
            ':pd' => '<pd>',
            ':sd' => '<sd>',
        ];

        return trim(strtr($normalized, $replacements), '/');
    }

    /**
     * @param list<string> $parameterNames
     * @param list<mixed> $invocationArgs
     */
    public static function normalizeSdkEndpoint(string $endpoint, array $parameterNames, array $invocationArgs): string
    {
        $normalized = trim($endpoint, '/');
        $placeholderCounts = [];

        foreach ($parameterNames as $index => $parameterName) {
            $value = $invocationArgs[$index] ?? null;
            if (!is_string($value) && !is_int($value) && !is_float($value)) {
                continue;
            }

            if (!self::isPathParameterName($parameterName)) {
                continue;
            }

            $occurrence = $placeholderCounts[$parameterName] ?? 0;
            $placeholderCounts[$parameterName] = $occurrence + 1;
            $placeholder = self::placeholderFromParameter($parameterName, $occurrence);
            $normalized = self::replacePathSegment($normalized, (string) $value, $placeholder);
        }

        return self::normalizePostmanEndpoint($normalized);
    }

    /**
     * @return list<string>
     */
    public static function extractPathParameters(string $endpoint): array
    {
        preg_match_all('/<[^>]+>/', $endpoint, $matches);
        return $matches[0] ?? [];
    }

    public static function staticEndpointBase(string $endpoint): string
    {
        $segments = array_values(array_filter(explode('/', $endpoint), static fn ($value) => $value !== ''));
        $staticSegments = [];

        foreach ($segments as $segment) {
            if (str_starts_with($segment, '<')) {
                break;
            }
            $staticSegments[] = $segment;
        }

        return implode('/', $staticSegments);
    }

    public static function sortDeep(mixed $value, ?string $path = null): mixed
    {
        if (is_array($value)) {
            if (self::isList($value)) {
                $sortedValues = array_map(
                    fn ($item) => self::sortDeep($item, $path),
                    $value
                );

                $allPrimitive = array_reduce(
                    $sortedValues,
                    static fn (bool $carry, mixed $item): bool => $carry && (!is_array($item) && !is_object($item)),
                    true
                );

                if ($allPrimitive) {
                    usort(
                        $sortedValues,
                        static fn (mixed $left, mixed $right): int => json_encode($left) <=> json_encode($right)
                    );
                }

                return $sortedValues;
            }

            $normalized = [];
            ksort($value);
            foreach ($value as $key => $item) {
                $normalized[(string) $key] = self::sortDeep(
                    self::normalizeVolatileValue((string) $key, $item),
                    (string) $key
                );
            }

            return $normalized;
        }

        return $value;
    }

    public static function inferShape(mixed $value): mixed
    {
        if (is_array($value)) {
            if (self::isList($value)) {
                if ($value === []) {
                    return ['unknown'];
                }

                return [self::inferShape($value[0])];
            }

            $shape = [];
            ksort($value);
            foreach ($value as $key => $item) {
                $shape[(string) $key] = self::inferShape($item);
            }

            return $shape;
        }

        if ($value === null) {
            return 'null';
        }

        return get_debug_type($value);
    }

    /**
     * @return list<string>
     */
    public static function diffValues(mixed $left, mixed $right, string $path = 'root', int $limit = 50): array
    {
        if ($limit <= 0) {
            return [];
        }

        if ($left === $right) {
            return [];
        }

        if (is_array($left) && is_array($right)) {
            $diffs = [];

            if (self::isList($left) && self::isList($right)) {
                if (count($left) !== count($right)) {
                    $diffs[] = sprintf('%s: array length differs (%d vs %d)', $path, count($left), count($right));
                }

                $max = max(count($left), count($right));
                for ($index = 0; $index < $max && count($diffs) < $limit; $index++) {
                    $diffs = array_merge(
                        $diffs,
                        self::diffValues(
                            $left[$index] ?? null,
                            $right[$index] ?? null,
                            sprintf('%s[%d]', $path, $index),
                            $limit - count($diffs)
                        )
                    );
                }

                return array_slice($diffs, 0, $limit);
            }

            $keys = array_unique(array_merge(array_keys($left), array_keys($right)));
            sort($keys);
            $diffs = [];
            foreach ($keys as $key) {
                $diffs = array_merge(
                    $diffs,
                    self::diffValues(
                        $left[$key] ?? null,
                        $right[$key] ?? null,
                        $path . '.' . (string) $key,
                        $limit - count($diffs)
                    )
                );
                if (count($diffs) >= $limit) {
                    break;
                }
            }

            return array_slice($diffs, 0, $limit);
        }

        return [sprintf('%s: %s !== %s', $path, json_encode($left), json_encode($right))];
    }

    public static function formatJsonBlock(mixed $value): string
    {
        return "```json\n" . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n```";
    }

    public static function serialiseResult(mixed $value, ?array $response = null): mixed
    {
        if ($response !== null && isset($response['bodyType']) && $response['bodyType'] !== 'text') {
            return $response['body'] ?? null;
        }

        if (is_string($value)) {
            if (str_starts_with($value, '%PDF')) {
                return [
                    'byteLength' => strlen($value),
                    'type' => 'pdf',
                    'isValidPdf' => true,
                ];
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    public static function isNotTestableStatus(?int $status): bool
    {
        return $status === 402 || $status === 403;
    }

    private static function isPathParameterName(string $name): bool
    {
        return in_array(
            $name,
            ['chartId', 'planetName', 'planet', 'zodiacName', 'zodiac', 'partnerZodiacName', 'partnerZodiac', 'md', 'ad', 'pd', 'sd'],
            true
        );
    }

    private static function placeholderFromParameter(string $name, int $occurrence): string
    {
        return match ($name) {
            'chartId' => '<chart>',
            'planetName', 'planet' => '<planet>',
            'zodiacName', 'zodiac' => $occurrence === 0 ? '<zodiac>' : '<zodiac2>',
            'partnerZodiacName', 'partnerZodiac' => '<zodiac2>',
            'md' => '<md>',
            'ad' => '<ad>',
            'pd' => '<pd>',
            'sd' => '<sd>',
            default => '<string>',
        };
    }

    private static function replacePathSegment(string $endpoint, string $value, string $placeholder): string
    {
        $segments = explode('/', $endpoint);
        foreach ($segments as $index => $segment) {
            if ($segment === $value || urldecode($segment) === $value) {
                $segments[$index] = $placeholder;
            }
        }

        return implode('/', $segments);
    }

    private static function normalizeVolatileValue(string $key, mixed $value): mixed
    {
        if (in_array($key, ['chart_url'], true) && is_string($value)) {
            $parts = parse_url($value);
            if ($parts === false) {
                return $value;
            }

            $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
            $host = $parts['host'] ?? '';
            $path = $parts['path'] ?? '';

            return $scheme . $host . $path;
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     */
    private static function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
