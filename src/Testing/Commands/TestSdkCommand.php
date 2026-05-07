<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing\Commands;

use AstrologyAPI\Testing\Docs;
use AstrologyAPI\Testing\Env;
use AstrologyAPI\Testing\Invocation;
use AstrologyAPI\Testing\Paths;
use AstrologyAPI\Testing\Postman;
use AstrologyAPI\Testing\Reporting;
use AstrologyAPI\Testing\Runners;
use AstrologyAPI\Testing\SdkInventory;
use AstrologyAPI\Testing\TestData;

final class TestSdkCommand
{
    public static function run(): int
    {
        Env::loadLocalEnv();
        Paths::ensureTestingDirectories();

        $generatedAt = Env::getTimestampLabel();
        $credentials = Env::getTestCredentials();
        $resolvedAuthStyle = Env::inferResolvedAuthStyle($credentials['apiKey']);
        $scenarios = TestData::loadTestScenarios();
        $postmanEntries = Postman::loadInventory();
        $sdkEntries = SdkInventory::buildSdkInventory($scenarios, $resolvedAuthStyle);

        Paths::writeJsonFile(Paths::catalogDir() . '/postman-endpoints.json', $postmanEntries);
        Paths::writeJsonFile(Paths::catalogDir() . '/sdk-endpoints.json', $sdkEntries);
        Paths::writeTextFile(
            Paths::resultsDir() . '/missing-apis.md',
            Reporting::buildMissingApisReport($postmanEntries, $sdkEntries, $resolvedAuthStyle, $generatedAt)
        );
        Paths::writeTextFile(
            Paths::resultsDir() . '/parameter-mismatches.md',
            Reporting::buildParameterMismatchReport($postmanEntries, $sdkEntries, $resolvedAuthStyle, $generatedAt)
        );

        $executionResults = [];
        $total = count($sdkEntries);

        foreach ($sdkEntries as $index => $entry) {
            $plan = Invocation::buildInvocationPlan(
                (string) $entry['module'],
                (string) $entry['method'],
                $entry['parameterNames'] ?? [],
                $scenarios,
                'deterministic'
            );
            $executionResults[] = Runners::executeSdkMethod($entry, $credentials, $plan);

            if ((($index + 1) % 10) === 0 || ($index + 1) === $total) {
                fwrite(STDOUT, sprintf("[qa-sdk] Processed %d/%d SDK methods\n", $index + 1, $total));
            }
        }

        Paths::writeTextFile(
            Paths::resultsDir() . '/failing-apis.md',
            Reporting::buildFailingApisReport($executionResults, $resolvedAuthStyle, $generatedAt)
        );
        Paths::writeTextFile(
            Paths::resultsDir() . '/not-testable-with-current-plan.md',
            Reporting::buildNotTestableReport($executionResults, $resolvedAuthStyle, $generatedAt)
        );
        Paths::writeTextFile(
            Paths::catalogDir() . '/sdk-modules.md',
            Docs::buildSdkModulesDocument($sdkEntries, $postmanEntries, $executionResults, $resolvedAuthStyle, $generatedAt)
        );
        Paths::writeTextFile(
            Paths::docsDir() . '/test-sdk.md',
            Docs::buildTestGuide($resolvedAuthStyle, $generatedAt)
        );

        $summary = [
            'total' => count($executionResults),
            'success' => count(array_filter($executionResults, static fn (array $result): bool => ($result['status'] ?? null) === 'success')),
            'failure' => count(array_filter($executionResults, static fn (array $result): bool => ($result['status'] ?? null) === 'failure')),
            'notTestable' => count(array_filter($executionResults, static fn (array $result): bool => ($result['status'] ?? null) === 'not-testable')),
        ];

        fwrite(
            STDOUT,
            sprintf(
                "[qa-sdk] Completed. success=%d failure=%d not-testable=%d total=%d\n",
                $summary['success'],
                $summary['failure'],
                $summary['notTestable'],
                $summary['total']
            )
        );

        return 0;
    }
}
