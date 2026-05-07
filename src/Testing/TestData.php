<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

use RuntimeException;

final class TestData
{
    /**
     * @return array<string,mixed>
     */
    public static function loadTestScenarios(): array
    {
        return [
            'birthStandard' => self::readJson('birth', 'standard.json'),
            'birthNegativeTimezone' => self::readJson('birth', 'negative-timezone.json'),
            'birthFractionalTimezone' => self::readJson('birth', 'fractional-timezone.json'),
            'birthDstSensitive' => self::readJson('birth', 'dst-sensitive.json'),
            'birthEdgeDate' => self::readJson('birth', 'edge-date.json'),
            'matchBasic' => self::readJson('match', 'basic-match.json'),
            'coupleBasic' => self::readJson('couple', 'basic-couple.json'),
            'numerologyBasic' => self::readJson('numerology', 'basic.json'),
            'numerologyAlternate' => self::readJson('numerology', 'alternate-name.json'),
            'pdfBranding' => self::readJson('pdf', 'basic-branding.json'),
            'zodiacPairs' => self::readJson('zodiac', 'basic-sign-pairs.json'),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function readJson(string ...$segments): array
    {
        $path = Paths::testDataDir() . '/' . implode('/', $segments);
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Unable to read test data file: ' . $path);
        }

        /** @var array<string,mixed> $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }
}
