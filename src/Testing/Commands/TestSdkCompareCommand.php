<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing\Commands;

use AstrologyAPI\Testing\Env;
use AstrologyAPI\Testing\Invocation;
use AstrologyAPI\Testing\Paths;
use AstrologyAPI\Testing\Postman;
use AstrologyAPI\Testing\Reporting;
use AstrologyAPI\Testing\Runners;
use AstrologyAPI\Testing\SdkInventory;
use AstrologyAPI\Testing\TestData;

final class TestSdkCompareCommand
{
    public static function run(): int
    {
        Env::loadLocalEnv();
        Paths::ensureTestingDirectories();

        $generatedAt = Env::getTimestampLabel();
        $credentials = Env::getTestCredentials();
        $resolvedAuthStyle = Env::inferResolvedAuthStyle($credentials['apiKey']);
        $compareCount = Env::getCompareCount();
        $scenarios = TestData::loadTestScenarios();
        $postmanEntries = Postman::loadInventory();
        $postmanMap = [];
        foreach ($postmanEntries as $entry) {
            $postmanMap[$entry['normalizedEndpoint']] = $entry;
        }

        $sdkEntries = SdkInventory::buildSdkInventory($scenarios, $resolvedAuthStyle);
        $candidates = array_values(array_filter(
            $sdkEntries,
            static fn (array $entry): bool => ($entry['normalizedEndpoint'] ?? '') !== '' && isset($postmanMap[$entry['normalizedEndpoint']])
        ));
        shuffle($candidates);
        $candidates = array_slice($candidates, 0, $compareCount);

        $comparisonResults = [];

        foreach ($candidates as $index => $entry) {
            $plan = Invocation::buildInvocationPlan(
                (string) $entry['module'],
                (string) $entry['method'],
                $entry['parameterNames'] ?? [],
                $scenarios,
                'random'
            );
            $sdkResult = Runners::executeSdkMethod($entry, $credentials, $plan);
            $directEntry = $postmanMap[$entry['normalizedEndpoint']] ?? null;
            $directResult = $directEntry !== null
                ? Runners::executeDirectRequest($directEntry, (string) $entry['normalizedEndpoint'], $credentials, $plan)
                : null;
            $comparison = Runners::compareExecutionResults($sdkResult, $directResult);

            if (
                $directEntry !== null &&
                $directResult !== null &&
                Runners::shouldRetryComparisonWithSdkFallback($sdkResult, $directResult, $comparison) &&
                is_array($sdkResult['sdkRequest'] ?? null)
            ) {
                $drift = Runners::findBodyShapeDrift($sdkResult['sdkRequest'], $directResult['request'] ?? null);
                $fallbackDirect = Runners::executeDirectRequestFromResolvedEndpoint(
                    (string) ($sdkResult['sdkRequest']['endpoint'] ?? $entry['endpoint']),
                    (string) ($sdkResult['sdkRequest']['normalizedEndpoint'] ?? $entry['normalizedEndpoint']),
                    (string) ($sdkResult['sdkRequest']['domain'] ?? $entry['domain']),
                    array_filter(
                        (array) ($sdkResult['sdkRequest']['body'] ?? $entry['requestBody']),
                        static fn (mixed $value): bool => $value !== null
                    ),
                    (string) ($sdkResult['sdkRequest']['encoding'] ?? $entry['requestEncoding']),
                    $credentials
                );
                $comparison = Runners::compareExecutionResults($sdkResult, $fallbackDirect);
                $comparison['notes'][] = 'Retried direct API execution with the SDK-resolved endpoint/body because the Postman request shape diverged from the SDK-captured request.';
                if (($drift['missingFromDirect'] ?? []) !== []) {
                    $comparison['notes'][] = 'Postman request was missing: ' . implode(', ', $drift['missingFromDirect']);
                }
                if (($drift['extraInDirect'] ?? []) !== []) {
                    $comparison['notes'][] = 'Postman request included stale fields: ' . implode(', ', $drift['extraInDirect']);
                }
            }

            $comparisonResults[] = $comparison;
            fwrite(STDOUT, sprintf("[qa-compare] Compared %d/%d -> %s\n", $index + 1, count($candidates), $entry['qualifiedName']));
        }

        Paths::writeTextFile(
            Paths::resultsDir() . '/inconsistent-responses.md',
            Reporting::buildComparisonReport($comparisonResults, $resolvedAuthStyle, $generatedAt)
        );

        $summary = [
            'total' => count($comparisonResults),
            'mismatches' => count(array_filter($comparisonResults, static fn (array $result): bool => !($result['consistent'] ?? false) && !isset($result['skippedReason']))),
            'skipped' => count(array_filter($comparisonResults, static fn (array $result): bool => isset($result['skippedReason']))),
        ];

        fwrite(
            STDOUT,
            sprintf(
                "[qa-compare] Completed. mismatches=%d skipped=%d total=%d\n",
                $summary['mismatches'],
                $summary['skipped'],
                $summary['total']
            )
        );

        return 0;
    }
}
