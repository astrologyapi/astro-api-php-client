<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

final class Reporting
{
    /**
     * @param list<array<string,mixed>> $postmanEntries
     * @param list<array<string,mixed>> $sdkEntries
     */
    public static function buildMissingApisReport(array $postmanEntries, array $sdkEntries, string $resolvedAuthStyle, string $generatedAt): string
    {
        $postmanSet = array_values(array_unique(array_map(static fn (array $entry): string => (string) $entry['normalizedEndpoint'], $postmanEntries)));
        $sdkSet = array_values(array_filter(array_unique(array_map(static fn (array $entry): string => (string) ($entry['normalizedEndpoint'] ?? ''), $sdkEntries))));

        $missingFromSdk = array_values(array_diff($postmanSet, $sdkSet));
        sort($missingFromSdk);
        $extraInSdk = array_values(array_diff($sdkSet, $postmanSet));
        sort($extraInSdk);

        return implode("\n", [
            '# Missing APIs',
            '',
            'Generated at: ' . $generatedAt,
            'Resolved auth style for the current QA run: `' . $resolvedAuthStyle . '`',
            '',
            '## Postman APIs Missing From SDK',
            '',
            ...self::renderEndpointList($missingFromSdk, 'Every Postman endpoint is currently represented in the SDK inventory.'),
            '',
            '## SDK APIs Absent From Postman Collections',
            '',
            ...self::renderEndpointList($extraInSdk, 'No extra SDK-only endpoints were found.'),
            '',
        ]);
    }

    /**
     * @param list<array<string,mixed>> $postmanEntries
     * @param list<array<string,mixed>> $sdkEntries
     */
    public static function buildParameterMismatchReport(array $postmanEntries, array $sdkEntries, string $resolvedAuthStyle, string $generatedAt): string
    {
        $postmanMap = [];
        foreach ($postmanEntries as $entry) {
            $postmanMap[$entry['normalizedEndpoint']] = $entry;
        }

        $exactMismatches = [];
        $pathMismatches = [];

        foreach ($sdkEntries as $sdkEntry) {
            $normalizedEndpoint = (string) ($sdkEntry['normalizedEndpoint'] ?? '');
            if ($normalizedEndpoint === '' || !isset($postmanMap[$normalizedEndpoint])) {
                continue;
            }

            $postmanEntry = $postmanMap[$normalizedEndpoint];
            $postmanFields = array_values(array_map(
                static fn (array $parameter): string => (string) $parameter['key'],
                array_filter(
                    $postmanEntry['bodyParameters'] ?? [],
                    static fn (array $parameter): bool => !($parameter['disabled'] ?? false)
                )
            ));
            sort($postmanFields);
            $sdkFields = array_values($sdkEntry['requestFields'] ?? []);
            sort($sdkFields);

            $missingInSdk = array_values(array_diff($postmanFields, $sdkFields));
            $extraInSdk = array_values(array_diff($sdkFields, $postmanFields));
            $encodingMismatch = ($postmanEntry['bodyMode'] ?? 'none') === 'urlencoded'
                && ($sdkEntry['requestEncoding'] ?? 'unknown') !== 'form-urlencoded';
            $authMismatch = ($postmanEntry['authStyles'] ?? []) !== []
                && !in_array($resolvedAuthStyle, $postmanEntry['authStyles'], true);

            if ($missingInSdk === [] && $extraInSdk === [] && !$encodingMismatch && !$authMismatch) {
                continue;
            }

            $exactMismatches[] = implode("\n", [
                '### ' . $sdkEntry['qualifiedName'],
                '- Endpoint: `' . $normalizedEndpoint . '`',
                '- Postman auth expectation: ' . Postman::authSummary($postmanEntry['authStyles'] ?? []),
                '- SDK request encoding: `' . ($sdkEntry['requestEncoding'] ?? 'unknown') . '`',
                '- Postman body mode: `' . ($postmanEntry['bodyMode'] ?? 'none') . '`',
                $missingInSdk !== [] ? '- Missing in SDK request: `' . implode('`, `', $missingInSdk) . '`' : '- Missing in SDK request: none',
                $extraInSdk !== [] ? '- Extra in SDK request: `' . implode('`, `', $extraInSdk) . '`' : '- Extra in SDK request: none',
                $encodingMismatch ? '- Body encoding mismatch: SDK is not sending Postman-style form-urlencoded payloads.' : '- Body encoding mismatch: none',
                $authMismatch ? '- Active auth-style mismatch: current run resolves to `' . $resolvedAuthStyle . '`, but Postman declares `' . Postman::authSummary($postmanEntry['authStyles'] ?? []) . '`.' : '- Active auth-style mismatch: none',
            ]);
        }

        $unmatchedPostman = array_values(array_filter(
            $postmanEntries,
            static fn (array $postmanEntry): bool => !array_reduce(
                $sdkEntries,
                static fn (bool $carry, array $sdkEntry): bool => $carry || (($sdkEntry['normalizedEndpoint'] ?? null) === $postmanEntry['normalizedEndpoint']),
                false
            )
        ));
        $unmatchedSdk = array_values(array_filter(
            $sdkEntries,
            static fn (array $sdkEntry): bool => ($sdkEntry['normalizedEndpoint'] ?? '') !== '' && !isset($postmanMap[$sdkEntry['normalizedEndpoint']])
        ));

        foreach ($unmatchedPostman as $postmanEntry) {
            foreach ($unmatchedSdk as $sdkEntry) {
                if (Normalize::staticEndpointBase((string) $sdkEntry['normalizedEndpoint']) === Normalize::staticEndpointBase((string) $postmanEntry['normalizedEndpoint'])
                    && Normalize::staticEndpointBase((string) $postmanEntry['normalizedEndpoint']) !== '') {
                    $pathMismatches[] = implode("\n", [
                        '### ' . $sdkEntry['qualifiedName'],
                        '- SDK endpoint: `' . $sdkEntry['normalizedEndpoint'] . '`',
                        '- Postman endpoint: `' . $postmanEntry['normalizedEndpoint'] . '`',
                        '- Likely issue: path parameters are missing or shaped differently in the SDK.',
                    ]);
                    break;
                }
            }
        }

        return implode("\n", [
            '# Parameter Mismatches',
            '',
            'Generated at: ' . $generatedAt,
            'Resolved auth style for the current QA run: `' . $resolvedAuthStyle . '`',
            '',
            '## Exact Endpoint Mismatches',
            '',
            ...($exactMismatches === [] ? ['No exact endpoint request-shape mismatches were detected in this run.'] : $exactMismatches),
            '',
            '## Path Parameter Mismatches',
            '',
            ...($pathMismatches === [] ? ['No path-parameter mismatches were detected in this run.'] : $pathMismatches),
            '',
        ]);
    }

    /**
     * @param list<array<string,mixed>> $results
     */
    public static function buildFailingApisReport(array $results, string $resolvedAuthStyle, string $generatedAt): string
    {
        $failingResults = array_values(array_filter(
            $results,
            static fn (array $result): bool => ($result['status'] ?? null) === 'failure'
        ));
        $groupedFailures = self::groupExecutionResultsByError($failingResults);

        $lines = [
            '# Failing APIs',
            '',
            'Generated at: ' . $generatedAt,
            'Resolved auth style for the current QA run: `' . $resolvedAuthStyle . '`',
            '',
        ];

        if ($failingResults === []) {
            $lines[] = 'No failing SDK API executions were captured in this run.';
            $lines[] = '';
            return implode("\n", $lines);
        }

        $lines[] = '## Error Summary';
        $lines[] = '';
        foreach ($groupedFailures as $group) {
            $count = count($group['results']);
            $lines[] = '- ' . $group['title'] . ': ' . $count . ' API' . ($count === 1 ? '' : 's');
        }

        $lines[] = '';
        foreach ($groupedFailures as $group) {
            $lines[] = '## ' . $group['title'];
            $lines[] = '';
            $lines[] = '- Affected APIs: ' . count($group['results']);
            $lines[] = '';
            $lines[] = '### API List';
            $lines[] = '';
            foreach ($group['results'] as $result) {
                $lines[] = '- `' . $result['qualifiedName'] . '` -> `' . $result['normalizedEndpoint'] . '`';
            }
            $lines[] = '';
            $lines[] = '### Detailed Captures';
            $lines[] = '';
            foreach ($group['results'] as $result) {
                $lines[] = self::renderExecutionSection($result, '####');
            }
        }

        $lines[] = '';
        return implode("\n", $lines);
    }

    /**
     * @param list<array<string,mixed>> $results
     */
    public static function buildNotTestableReport(array $results, string $resolvedAuthStyle, string $generatedAt): string
    {
        $notTestableResults = array_values(array_filter(
            $results,
            static fn (array $result): bool => ($result['status'] ?? null) === 'not-testable'
        ));

        return implode("\n", [
            '# Not Testable With Current Plan Or Credentials',
            '',
            'Generated at: ' . $generatedAt,
            'Resolved auth style for the current QA run: `' . $resolvedAuthStyle . '`',
            '',
            ...($notTestableResults === []
                ? ['No plan-restricted or quota-restricted SDK API executions were captured in this run.']
                : array_map([self::class, 'renderExecutionSection'], $notTestableResults)),
            '',
        ]);
    }

    /**
     * @param list<array<string,mixed>> $results
     */
    public static function buildComparisonReport(array $results, string $resolvedAuthStyle, string $generatedAt): string
    {
        $mismatches = array_values(array_filter(
            $results,
            static fn (array $result): bool => !($result['consistent'] ?? false) && !isset($result['skippedReason'])
        ));
        $skipped = array_values(array_filter(
            $results,
            static fn (array $result): bool => isset($result['skippedReason'])
        ));

        return implode("\n", [
            '# Inconsistent Responses',
            '',
            'Generated at: ' . $generatedAt,
            'Resolved auth style for the current QA run: `' . $resolvedAuthStyle . '`',
            '',
            '## Mismatches',
            '',
            ...($mismatches === [] ? ['No inconsistent SDK vs direct API responses were found in this compare run.'] : array_map([self::class, 'renderComparisonSection'], $mismatches)),
            '',
            '## Skipped / Not Testable',
            '',
            ...($skipped === []
                ? ['No compare candidates were skipped in this run.']
                : array_map(
                    static fn (array $result): string => implode("\n", [
                        '### ' . $result['qualifiedName'],
                        '- Endpoint: `' . $result['normalizedEndpoint'] . '`',
                        '- Reason: ' . $result['skippedReason'],
                    ]),
                    $skipped
                )),
            '',
        ]);
    }

    /**
     * @param list<string> $endpoints
     * @return list<string>
     */
    private static function renderEndpointList(array $endpoints, string $emptyMessage): array
    {
        if ($endpoints === []) {
            return [$emptyMessage];
        }

        return array_map(static fn (string $endpoint): string => '- `' . $endpoint . '`', $endpoints);
    }

    /**
     * @param array<string,mixed> $result
     */
    private static function renderExecutionSection(array $result, string $heading = '##'): string
    {
        return implode("\n", [
            $heading . ' ' . $result['qualifiedName'],
            '',
            '- Endpoint: `' . $result['normalizedEndpoint'] . '`',
            isset($result['errorStatus']) ? '- Status code: ' . $result['errorStatus'] : '- Status code: unavailable',
            isset($result['errorName']) ? '- Error: `' . $result['errorName'] . '`' : '- Error: unavailable',
            isset($result['errorMessage']) ? '- Message: ' . $result['errorMessage'] : '- Message: unavailable',
            '',
            '### Request',
            '',
            Normalize::formatJsonBlock([
                'invocationArgs' => $result['invocationArgs'] ?? [],
                'request' => $result['sdkRequest'] ?? null,
            ]),
            '',
            '### Response',
            '',
            Normalize::formatJsonBlock([
                'sdkResponse' => $result['sdkResponse'] ?? null,
                'resultSnapshot' => $result['resultSnapshot'] ?? null,
            ]),
            '',
        ]);
    }

    /**
     * @param list<array<string,mixed>> $results
     * @return list<array{title: string, results: list<array<string,mixed>>}>
     */
    private static function groupExecutionResultsByError(array $results): array
    {
        $groups = [];

        foreach ($results as $result) {
            $title = self::buildErrorGroupTitle($result);
            $groups[$title]['title'] = $title;
            $groups[$title]['results'][] = $result;
        }

        $grouped = array_values($groups);
        usort(
            $grouped,
            static function (array $left, array $right): int {
                $countCompare = count($right['results']) <=> count($left['results']);
                return $countCompare !== 0 ? $countCompare : ($left['title'] <=> $right['title']);
            }
        );

        return $grouped;
    }

    /**
     * @param array<string,mixed> $result
     */
    private static function buildErrorGroupTitle(array $result): string
    {
        $parts = [];
        if (isset($result['errorStatus'])) {
            $parts[] = 'HTTP ' . $result['errorStatus'];
        }
        if (isset($result['errorName'])) {
            $parts[] = $result['errorName'];
        }
        if (isset($result['errorMessage'])) {
            $parts[] = self::summarizeMessage((string) $result['errorMessage']);
        }

        return $parts === [] ? 'Unclassified Failure' : implode(' | ', $parts);
    }

    private static function summarizeMessage(string $message): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($message)) ?? $message;
        return strlen($normalized) > 140 ? substr($normalized, 0, 137) . '...' : $normalized;
    }

    /**
     * @param array<string,mixed> $result
     */
    private static function renderComparisonSection(array $result): string
    {
        $noteLines = array_map(
            static fn (string $note): string => '- Note: ' . $note,
            $result['notes'] ?? []
        );

        $diffSummary = ($result['diffLines'] ?? []) === []
            ? '- Diff summary: none'
            : '- Diff summary:' . "\n" . implode("\n", array_map(
                static fn (string $line): string => '  - ' . $line,
                $result['diffLines']
            ));

        return implode("\n", [
            '### ' . $result['qualifiedName'],
            '- Endpoint: `' . $result['normalizedEndpoint'] . '`',
            ...$noteLines,
            $diffSummary,
            '',
            '#### SDK Request / Response',
            '',
            Normalize::formatJsonBlock([
                'request' => $result['sdk']['sdkRequest'] ?? null,
                'response' => $result['sdk']['sdkResponse'] ?? null,
                'resultSnapshot' => $result['sdk']['resultSnapshot'] ?? null,
            ]),
            '',
            '#### Direct API Request / Response',
            '',
            Normalize::formatJsonBlock([
                'request' => $result['direct']['request'] ?? null,
                'response' => $result['direct']['response'] ?? null,
                'resultSnapshot' => $result['direct']['resultSnapshot'] ?? null,
            ]),
            '',
        ]);
    }
}
