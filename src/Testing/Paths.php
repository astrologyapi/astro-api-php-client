<?php

declare(strict_types=1);

namespace AstrologyAPI\Testing;

final class Paths
{
    public static function rootDir(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function testingDir(): string
    {
        return self::rootDir() . '/testing';
    }

    public static function catalogDir(): string
    {
        return self::testingDir() . '/catalog';
    }

    public static function docsDir(): string
    {
        return self::testingDir() . '/docs';
    }

    public static function resultsDir(): string
    {
        return self::testingDir() . '/results';
    }

    public static function testDataDir(): string
    {
        return self::testingDir() . '/test-data';
    }

    public static function postmanDir(): string
    {
        return self::testingDir() . '/postman';
    }

    /**
     * @return list<string>
     */
    public static function postmanCollectionFiles(): array
    {
        $discovered = glob(self::postmanDir() . '/*_postman_collection.json') ?: [];
        if ($discovered !== []) {
            $files = array_map('basename', $discovered);
            sort($files);
            return array_values($files);
        }

        return [
            'vedic_astrology_API_collection_postman_collection.json',
            'vedic_astrology_pdf_API_collection_postman_collection.json',
            'western_astrology_API_collection_postman_collection.json',
            'western_astrology_pdf_API_collection_postman_collection.json',
        ];
    }

    public static function ensureTestingDirectories(): void
    {
        foreach ([self::catalogDir(), self::docsDir(), self::resultsDir()] as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    public static function writeTextFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $content);
    }

    public static function writeJsonFile(string $path, mixed $value): void
    {
        self::writeTextFile(
            $path,
            (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }
}
