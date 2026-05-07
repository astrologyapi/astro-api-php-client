<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing\Commands;

use AstrologyAPI\Testing\Env;
use AstrologyAPI\Testing\Invocation;
use AstrologyAPI\Testing\Postman;
use AstrologyAPI\Testing\Runners;
use AstrologyAPI\Testing\SdkInventory;
use AstrologyAPI\Testing\TestData;

final class TestSingleApiCommand
{
    public static function run(): int
    {
        Env::loadLocalEnv();

        $generatedAt = Env::getTimestampLabel();
        $sdkTarget = trim((string) (getenv('ASTROLOGYAPI_SDK_TARGET') ?: 'vedic.getBirthDetails'));
        $directTargetLookup = trim((string) (getenv('ASTROLOGYAPI_DIRECT_TARGET_LOOKUP') ?: ''));
        $strategy = trim((string) (getenv('ASTROLOGYAPI_SINGLE_STRATEGY') ?: 'deterministic'));

        $credentials = Env::getTestCredentials();
        $resolvedAuthStyle = Env::inferResolvedAuthStyle($credentials['apiKey']);
        $scenarios = TestData::loadTestScenarios();
        $sdkEntries = SdkInventory::buildSdkInventory($scenarios, $resolvedAuthStyle);
        $postmanEntries = Postman::loadInventory();

        $sdkEntry = self::resolveSdkEntry($sdkEntries, $sdkTarget);
        $directLookup = $directTargetLookup !== '' ? $directTargetLookup : (string) $sdkEntry['normalizedEndpoint'];
        $directResolution = self::resolvePostmanEntry($postmanEntries, $directLookup);
        $plan = Invocation::buildInvocationPlan(
            (string) $sdkEntry['module'],
            (string) $sdkEntry['method'],
            $sdkEntry['parameterNames'] ?? [],
            $scenarios,
            $strategy === 'random' ? 'random' : 'deterministic'
        );

        self::printSection('Single API Debugger');
        self::printJson([
            'generatedAt' => $generatedAt,
            'sdkTarget' => $sdkTarget,
            'directTargetLookup' => $directLookup,
            'resolvedAuthStyle' => $resolvedAuthStyle,
            'invocationStrategy' => $strategy,
        ]);

        self::printSection('Resolved SDK Target');
        self::printJson([
            'qualifiedName' => $sdkEntry['qualifiedName'],
            'normalizedEndpoint' => $sdkEntry['normalizedEndpoint'],
            'parameterNames' => $sdkEntry['parameterNames'],
            'requestFields' => $sdkEntry['requestFields'],
            'requestEncoding' => $sdkEntry['requestEncoding'],
        ]);

        self::printSection('Resolved Direct Target');
        self::printJson(self::buildDirectResolutionSummary($directResolution, $sdkEntry));

        self::printSection('Invocation Arguments');
        self::printJson([
            'args' => $plan['args'],
            'context' => $plan['context'],
        ]);

        $sdkResult = Runners::executeSdkMethod($sdkEntry, $credentials, $plan);
        self::printExecutionResult('SDK Execution', $sdkResult);

        $initialDirectResult = isset($directResolution['entry'])
            ? Runners::executeDirectRequest($directResolution['entry'], (string) $directResolution['entry']['normalizedEndpoint'], $credentials, $plan)
            : Runners::executeDirectRequestFromResolvedEndpoint(
                (string) (($sdkResult['sdkRequest']['endpoint'] ?? null) ?: $sdkEntry['endpoint']),
                (string) (($sdkResult['sdkRequest']['normalizedEndpoint'] ?? null) ?: $sdkEntry['normalizedEndpoint']),
                (string) (($sdkResult['sdkRequest']['domain'] ?? null) ?: $sdkEntry['domain']),
                array_filter(
                    (array) (($sdkResult['sdkRequest']['body'] ?? null) ?: $sdkEntry['requestBody']),
                    static fn (mixed $value): bool => $value !== null
                ),
                (string) (($sdkResult['sdkRequest']['encoding'] ?? null) ?: $sdkEntry['requestEncoding']),
                $credentials
            );

        $directResult = $initialDirectResult;
        $comparison = Runners::compareExecutionResults($sdkResult, $initialDirectResult);
        $initialComparison = $comparison;
        if (
            isset($directResolution['entry']) &&
            is_array($sdkResult['sdkRequest'] ?? null) &&
            (
                Runners::shouldRetryWithSdkFallback($initialDirectResult, $sdkResult) ||
                Runners::shouldRetryComparisonWithSdkFallback($sdkResult, $initialDirectResult, $comparison)
            )
        ) {
            $missingFields = Runners::findSdkOnlyValidationFields($initialDirectResult, $sdkResult);
            $drift = Runners::findBodyShapeDrift($sdkResult['sdkRequest'], $initialDirectResult['request'] ?? null);

            self::printDirectExecutionResult('Direct API Execution (Postman)', $initialDirectResult);

            $directResult = Runners::executeDirectRequestFromResolvedEndpoint(
                (string) ($sdkResult['sdkRequest']['endpoint'] ?? $sdkEntry['endpoint']),
                (string) ($sdkResult['sdkRequest']['normalizedEndpoint'] ?? $sdkEntry['normalizedEndpoint']),
                (string) ($sdkResult['sdkRequest']['domain'] ?? $sdkEntry['domain']),
                array_filter((array) ($sdkResult['sdkRequest']['body'] ?? $sdkEntry['requestBody']), static fn (mixed $value): bool => $value !== null),
                (string) ($sdkResult['sdkRequest']['encoding'] ?? $sdkEntry['requestEncoding']),
                $credentials
            );
            $comparison = Runners::compareExecutionResults($sdkResult, $directResult);

            if ($missingFields !== []) {
                self::printSection('Fallback Decision');
                self::printJson([
                    'reason' => 'Retried direct API execution with the SDK-resolved request body because the Postman-derived body failed validation for fields present in the SDK request.',
                    'missingFields' => $missingFields,
                ]);
            } else {
                $comparison['notes'][] = 'Retried direct API execution with the SDK-resolved endpoint/body because the Postman request shape diverged from the SDK-captured request.';
                if (($drift['missingFromDirect'] ?? []) !== []) {
                    $comparison['notes'][] = 'Postman request was missing: ' . implode(', ', $drift['missingFromDirect']);
                }
                if (($drift['extraInDirect'] ?? []) !== []) {
                    $comparison['notes'][] = 'Postman request included stale fields: ' . implode(', ', $drift['extraInDirect']);
                }

                self::printSection('Fallback Decision');
                self::printJson([
                    'reason' => 'Retried direct API execution with the SDK-resolved endpoint/body because the Postman-derived request shape diverged from the SDK-captured request and the initial comparison was inconsistent.',
                    'missingFromDirect' => $drift['missingFromDirect'] ?? [],
                    'extraInDirect' => $drift['extraInDirect'] ?? [],
                    'initialDiffLines' => $initialComparison['diffLines'] ?? [],
                ]);
            }

            self::printDirectExecutionResult('Direct API Execution (SDK Fallback)', $directResult);
        } else {
            self::printDirectExecutionResult('Direct API Execution', $directResult);
        }

        self::printSection('Comparison Summary');
        self::printJson([
            'consistent' => $comparison['consistent'],
            'skippedReason' => $comparison['skippedReason'] ?? null,
            'diffCount' => count($comparison['diffLines'] ?? []),
            'diffLines' => $comparison['diffLines'] ?? [],
            'notes' => $comparison['notes'] ?? [],
        ]);

        return !($comparison['consistent'] ?? false) || isset($comparison['skippedReason']) ? 1 : 0;
    }

    /**
     * @param list<array<string,mixed>> $entries
     * @return array<string,mixed>
     */
    private static function resolveSdkEntry(array $entries, string $target): array
    {
        foreach ($entries as $entry) {
            if (($entry['qualifiedName'] ?? null) === $target) {
                return $entry;
            }
        }

        $suggestions = array_values(array_map(
            static fn (array $entry): string => (string) $entry['qualifiedName'],
            array_filter(
                $entries,
                static fn (array $entry): bool => str_contains(strtolower((string) $entry['qualifiedName']), strtolower($target))
            )
        ));

        $message = sprintf("SDK target \"%s\" was not found.\n", $target);
        $message .= $suggestions !== []
            ? 'Closest SDK matches: ' . implode(', ', array_slice($suggestions, 0, 12))
            : 'No similar SDK methods were found.';

        throw new \RuntimeException($message);
    }

    /**
     * @param list<array<string,mixed>> $entries
     * @return array{entry?: array<string,mixed>, suggestions: list<string>, fallbackReason?: string}
     */
    private static function resolvePostmanEntry(array $entries, string $lookup): array
    {
        $normalizedLookup = strtolower(trim($lookup));
        foreach ($entries as $entry) {
            if (self::matchesPostmanLookup($entry, $normalizedLookup, false)) {
                return ['entry' => $entry, 'suggestions' => []];
            }
        }

        $suggestions = array_values(array_map(
            static fn (array $entry): string => (string) $entry['normalizedEndpoint'],
            array_filter($entries, static fn (array $entry): bool => self::matchesPostmanLookup($entry, $normalizedLookup, true))
        ));

        return [
            'suggestions' => array_slice($suggestions, 0, 12),
            'fallbackReason' => 'Direct API target "' . $lookup . '" was not found in the Postman inventory.',
        ];
    }

    /**
     * @param array<string,mixed> $entry
     */
    private static function matchesPostmanLookup(array $entry, string $lookup, bool $fuzzy): bool
    {
        $candidates = array_map(
            static fn (string $value): string => strtolower($value),
            array_merge(
                [(string) $entry['normalizedEndpoint'], (string) $entry['endpoint']],
                array_map(static fn (mixed $value): string => (string) $value, $entry['displayNames'] ?? [])
            )
        );

        foreach ($candidates as $candidate) {
            if ($fuzzy ? str_contains($candidate, $lookup) : $candidate === $lookup) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{entry?: array<string,mixed>, suggestions: list<string>, fallbackReason?: string} $resolution
     * @param array<string,mixed> $sdkEntry
     * @return array<string,mixed>
     */
    private static function buildDirectResolutionSummary(array $resolution, array $sdkEntry): array
    {
        if (isset($resolution['entry'])) {
            return [
                'resolutionStrategy' => 'postman',
                'normalizedEndpoint' => $resolution['entry']['normalizedEndpoint'],
                'endpoint' => $resolution['entry']['endpoint'],
                'displayNames' => $resolution['entry']['displayNames'],
                'bodyMode' => $resolution['entry']['bodyMode'],
                'bodyParameters' => array_map(
                    static fn (array $parameter): string => (string) $parameter['key'],
                    array_filter($resolution['entry']['bodyParameters'] ?? [], static fn (array $parameter): bool => !($parameter['disabled'] ?? false))
                ),
                'authStyles' => $resolution['entry']['authStyles'],
            ];
        }

        return [
            'resolutionStrategy' => 'sdk-fallback',
            'fallbackReason' => $resolution['fallbackReason'] ?? null,
            'sdkEndpoint' => $sdkEntry['endpoint'],
            'sdkNormalizedEndpoint' => $sdkEntry['normalizedEndpoint'],
            'sdkDomain' => $sdkEntry['domain'],
            'sdkRequestEncoding' => $sdkEntry['requestEncoding'],
            'sdkRequestFields' => $sdkEntry['requestFields'],
            'suggestions' => $resolution['suggestions'],
        ];
    }

    /**
     * @param array<string,mixed> $result
     */
    private static function printExecutionResult(string $title, array $result): void
    {
        self::printSection($title);
        self::printJson([
            'status' => $result['status'],
            'errorStatus' => $result['errorStatus'] ?? null,
            'errorName' => $result['errorName'] ?? null,
            'errorMessage' => $result['errorMessage'] ?? null,
            'request' => $result['sdkRequest'] ?? null,
            'response' => $result['sdkResponse'] ?? null,
            'resultSnapshot' => $result['resultSnapshot'] ?? null,
            'responseShape' => $result['responseShape'] ?? null,
        ]);
    }

    /**
     * @param array<string,mixed> $result
     */
    private static function printDirectExecutionResult(string $title, array $result): void
    {
        self::printSection($title);
        self::printJson([
            'status' => $result['status'],
            'errorStatus' => $result['errorStatus'] ?? null,
            'errorName' => $result['errorName'] ?? null,
            'errorMessage' => $result['errorMessage'] ?? null,
            'request' => $result['request'] ?? null,
            'response' => $result['response'] ?? null,
            'resultSnapshot' => $result['resultSnapshot'] ?? null,
        ]);
    }

    private static function printSection(string $title): void
    {
        fwrite(STDOUT, "\n=== " . $title . " ===\n");
    }

    private static function printJson(mixed $value): void
    {
        fwrite(STDOUT, (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}
