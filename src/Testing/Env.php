<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

use RuntimeException;

final class Env
{
    public static function loadLocalEnv(): void
    {
        $paths = [
            Paths::rootDir() . '/.env',
            Paths::rootDir() . '/.env.local',
        ];

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $raw = file_get_contents($path);
            if ($raw === false) {
                continue;
            }

            foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
                $trimmed = trim($line);
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    continue;
                }

                $equalsIndex = strpos($trimmed, '=');
                if ($equalsIndex === false) {
                    continue;
                }

                $key = trim(substr($trimmed, 0, $equalsIndex));
                $value = trim(substr($trimmed, $equalsIndex + 1));
                $value = trim($value, "\"'");

                if ($key !== '' && getenv($key) === false) {
                    putenv($key . '=' . $value);
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    public static function inferResolvedAuthStyle(string $apiKey): string
    {
        return str_contains($apiKey, 'ak-') ? 'header' : 'basic';
    }

    public static function getCompareCount(): int
    {
        $raw = getenv('ASTROLOGYAPI_TEST_COMPARE_COUNT');
        $parsed = $raw !== false ? (int) $raw : 8;

        return $parsed > 0 ? $parsed : 8;
    }

    /**
     * @return array{userId: string|null, apiKey: string}
     */
    public static function getTestCredentials(): array
    {
        $apiKey = trim((string) (getenv('ASTROLOGYAPI_API_KEY') ?: ''));
        if ($apiKey === '') {
            throw new RuntimeException(
                'Missing AstrologyAPI credentials. Set ASTROLOGYAPI_API_KEY in the environment or .env file.'
            );
        }

        $userId = trim((string) (getenv('ASTROLOGYAPI_USER_ID') ?: ''));
        if (self::inferResolvedAuthStyle($apiKey) === 'basic' && $userId === '') {
            throw new RuntimeException(
                'Missing ASTROLOGYAPI_USER_ID for Basic Authorization credentials.'
            );
        }

        return [
            'userId' => $userId !== '' ? $userId : null,
            'apiKey' => $apiKey,
        ];
    }

    public static function getTimestampLabel(?\DateTimeInterface $date = null): string
    {
        $date ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $date->format(DATE_ATOM);
    }
}
