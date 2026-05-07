<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

final class Docs
{
    /**
     * @param list<array<string,mixed>> $sdkEntries
     * @param list<array<string,mixed>> $postmanEntries
     * @param list<array<string,mixed>> $executionResults
     */
    public static function buildSdkModulesDocument(
        array $sdkEntries,
        array $postmanEntries,
        array $executionResults,
        string $resolvedAuthStyle,
        string $generatedAt
    ): string {
        $postmanMap = [];
        foreach ($postmanEntries as $entry) {
            $postmanMap[$entry['normalizedEndpoint']] = $entry;
        }

        $executionMap = [];
        foreach ($executionResults as $result) {
            $executionMap[$result['qualifiedName']] = $result;
        }

        $groupedEntries = [];
        foreach ($sdkEntries as $entry) {
            $groupedEntries[$entry['module']][] = $entry;
        }

        $sections = [
            '# PHP SDK Modules',
            '',
            'Generated at: ' . $generatedAt,
            'Resolved auth style used for the latest live SDK run: `' . $resolvedAuthStyle . '`',
            '',
        ];

        foreach ($groupedEntries as $moduleName => $entries) {
            $sections[] = '## ' . $moduleName;
            $sections[] = '';

            foreach ($entries as $entry) {
                $postmanEntry = $postmanMap[$entry['normalizedEndpoint']] ?? null;
                $executionResult = $executionMap[$entry['qualifiedName']] ?? null;
                $responseShape = $executionResult['responseShape'] ?? 'Not inferred in this run';

                $sections[] = '### ' . $entry['method'];
                $sections[] = '- Qualified SDK call: `' . self::formatQualifiedSdkCall((string) $entry['qualifiedName']) . '`';
                $sections[] = '- Endpoint: `' . ($entry['normalizedEndpoint'] ?: ($entry['endpoint'] ?: 'unresolved')) . '`';
                $sections[] = '- Domain: `' . $entry['domain'] . '`';
                $sections[] = '- Observed SDK auth style: `' . $entry['resolvedAuthStyle'] . '`';
                $sections[] = '- Postman coverage: ' . ($postmanEntry !== null ? 'covered' : 'missing from Postman collections');
                $sections[] = '- Postman auth expectation: ' . ($postmanEntry !== null ? Postman::authSummary($postmanEntry['authStyles'] ?? []) : 'not available');
                $sections[] = '- SDK parameter names: `' . (($entry['parameterNames'] ?? []) !== [] ? implode('`, `', $entry['parameterNames']) : 'none') . '`';
                $sections[] = '- SDK request encoding: `' . $entry['requestEncoding'] . '`';
                $sections[] = '- SDK request body keys: `' . (($entry['requestFields'] ?? []) !== [] ? implode('`, `', $entry['requestFields']) : 'none') . '`';
                $sections[] = '- Postman request body keys: `' . (
                    $postmanEntry !== null
                        ? implode('`, `', array_map(
                            static fn (array $parameter): string => (string) $parameter['key'],
                            array_filter($postmanEntry['bodyParameters'] ?? [], static fn (array $parameter): bool => !($parameter['disabled'] ?? false))
                        )) ?: 'none'
                        : 'none'
                ) . '`';
                $sections[] = '';
                $sections[] = '#### Request Body';
                $sections[] = '';
                $sections[] = Normalize::formatJsonBlock($entry['requestBody']);
                $sections[] = '';
                $sections[] = '#### Inferred Response Shape';
                $sections[] = '';
                $sections[] = Normalize::formatJsonBlock($responseShape);
                $sections[] = '';
            }
        }

        return implode("\n", $sections);
    }

    private static function formatQualifiedSdkCall(string $qualifiedName): string
    {
        $parts = explode('.', $qualifiedName);

        if (($parts[0] ?? null) === 'pdf' && isset($parts[1], $parts[2])) {
            return 'client->pdf()->' . $parts[1] . '->' . $parts[2] . '(...)';
        }

        if (isset($parts[0], $parts[1])) {
            return 'client->' . $parts[0] . '()->' . $parts[1] . '(...)';
        }

        return 'client->' . str_replace('.', '->', $qualifiedName) . '(...)';
    }

    public static function buildTestGuide(string $resolvedAuthStyle, string $generatedAt): string
    {
        return implode("\n", [
            '# PHP SDK Test Guide',
            '',
            'Generated at: ' . $generatedAt,
            'Most recent documented resolved auth style: `' . $resolvedAuthStyle . '`',
            '',
            '## What This QA Layer Covers',
            '',
            '- Postman collection coverage against the PHP SDK surface',
            '- Missing APIs and SDK-only APIs',
            '- Request parameter mismatches and body-shape mismatches',
            '- Full SDK execution checks for every discovered PHP SDK method',
            '- Randomized SDK vs direct API comparisons',
            '- Single-endpoint debugging with editable SDK and Postman targets',
            '- Separate handling for plan-restricted endpoints (`402` / `403`)',
            '- PDF validation using either a non-empty binary PDF payload or a successful JSON response containing `pdf_url`',
            '',
            '## Authentication Resolution',
            '',
            'The SDK and QA runners infer authentication from the configured `ASTROLOGYAPI_API_KEY`:',
            '',
            '- if the API key contains `ak-`, requests use `x-astrologyapi-key`',
            '- otherwise requests use `Authorization: Basic base64(userId:apiKey)`',
            '- no separate auth flag is required',
            '',
            '## Output Files',
            '',
            '- `testing/catalog/sdk-modules.md` documents module functions, request bodies, and inferred response shapes',
            '- `testing/catalog/sdk-endpoints.json` stores the machine-readable PHP SDK inventory',
            '- `testing/results/missing-apis.md` highlights collection/SDK coverage gaps',
            '- `testing/results/parameter-mismatches.md` highlights request-shape differences',
            '- `testing/results/failing-apis.md` captures failed SDK executions grouped by error signature',
            '- `testing/results/not-testable-with-current-plan.md` captures `402` / `403` cases separately',
            '- `testing/results/inconsistent-responses.md` captures randomized SDK vs direct API mismatches',
            '',
            '## Commands',
            '',
            '- `php bin/qa-sdk.php` runs the deterministic sweep and regenerates catalogs/docs/reports',
            '- `php bin/qa-compare.php` runs the randomized SDK-vs-direct comparison',
            '- `php bin/qa-single.php` debugs one SDK method with stale-Postman fallback support',
            '',
            '## Single Endpoint Debugging',
            '',
            'Configure these environment variables before running `php bin/qa-single.php`:',
            '',
            '- `ASTROLOGYAPI_SDK_TARGET` such as `vedic.getVarshaphalMuntha` or `pdf.western.getNatalChart`',
            '- `ASTROLOGYAPI_DIRECT_TARGET_LOOKUP` to force a Postman lookup by normalized endpoint, raw endpoint, or item name fragment',
            '- `ASTROLOGYAPI_SINGLE_STRATEGY` as `deterministic` or `random`',
            '',
            'Behavior:',
            '',
            '- if no Postman match is found, the runner falls back to the SDK-resolved endpoint and request body',
            '- if a Postman match exists but its request body is stale, missing SDK-captured fields, or diverges from the SDK-captured request shape, the runner retries with the SDK-resolved request body and prints the fallback reason',
            '',
        ]);
    }
}
