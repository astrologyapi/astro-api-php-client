<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use AstrologyAPI\Client;
use AstrologyAPI\Models\BirthData;
use AstrologyAPI\Models\PDFBranding;
use AstrologyAPI\Errors\PlanRestrictedError;
use AstrologyAPI\Errors\QuotaExceededError;
use AstrologyAPI\Errors\AstrologyAPIException;

/**
 * AstrologyAPI PHP SDK — Advanced Usage Examples
 *
 * Before running:
 *   export ASTROLOGYAPI_API_KEY="your-api-key"
 *   composer install
 *   php examples/advanced_usage.php
 */

$client = Client::fromEnv();

$birth = new BirthData(
    day: 15, month: 8, year: 1990,
    hour: 14, min: 30,
    lat: 28.6139, lon: 77.2090, tzone: 5.5,
);

// ── Vedic Matchmaking ─────────────────────────────────────────────────
// Male and female birth data are sent with m_ and f_ prefixes respectively.
$male   = new BirthData(day: 15, month: 8, year: 1990, hour: 14, min: 30, lat: 28.6139, lon: 77.2090, tzone: 5.5);
$female = new BirthData(day: 20, month: 3, year: 1992, hour: 10, min: 15, lat: 19.0760, lon: 72.8777, tzone: 5.5);

echo "=== Matchmaking Report ===\n";
$match = $client->vedic()->getMatchMakingReport($male, $female);
print_r($match);

echo "\n=== Ashtakoot Points ===\n";
$ashtakoot = $client->vedic()->getAshtakootPoints($male, $female);
print_r($ashtakoot);

// ── Western Synastry ──────────────────────────────────────────────────
// Person 1 fields are prefixed p_, person 2 fields are prefixed s_.
echo "\n=== Synastry Horoscope ===\n";
$synastry = $client->western()->getSynastry($male, $female);
print_r($synastry);

// ── PDF Reports ───────────────────────────────────────────────────────
// All PDF methods return raw binary string. Write to file or stream to browser.
$branding = new PDFBranding(
    logoUrl:     'https://example.com/logo.png',
    companyName: 'My Astrology Business',
    domainUrl:   'https://example.com',
    companyEmail: 'hello@example.com',
);

echo "\n=== Generating Basic Horoscope PDF ===\n";
$pdf = $client->pdf()->vedic->getBasicHoroscope($birth, $branding, ['name' => 'Rahul']);
file_put_contents('/tmp/horoscope.pdf', $pdf);
echo "Saved " . strlen($pdf) . " bytes to /tmp/horoscope.pdf\n";

echo "\n=== Generating Match Making PDF ===\n";
$matchPdf = $client->pdf()->vedic->getMatchMaking($male, $female, $branding, [
    'name'         => 'Rahul',
    'partner_name' => 'Priya',
]);
file_put_contents('/tmp/match_making.pdf', $matchPdf);
echo "Saved " . strlen($matchPdf) . " bytes to /tmp/match_making.pdf\n";

echo "\n=== Generating Western Natal Chart PDF ===\n";
$natalPdf = $client->pdf()->western->getNatalChart($birth, $branding);
file_put_contents('/tmp/natal_chart.pdf', $natalPdf);
echo "Saved " . strlen($natalPdf) . " bytes to /tmp/natal_chart.pdf\n";

// ── Location Helpers ──────────────────────────────────────────────────
echo "\n=== Geo Details ===\n";
$geo = $client->location()->getGeoDetails('New Delhi, India', maxRows: 3);
print_r($geo);

// ── Horoscope Predictions ─────────────────────────────────────────────
echo "\n=== Daily Horoscope for Aries ===\n";
$horoscope = $client->horoscopes()->getDaily('aries');
print_r($horoscope);

echo "\n=== Consolidated Daily Horoscope for Scorpio ===\n";
$consolidated = $client->horoscopes()->getDailyConsolidated('scorpio');
print_r($consolidated);

// ── Tarot Reading ─────────────────────────────────────────────────────
echo "\n=== Daily Tarot Card ===\n";
$tarot = $client->tarot()->getDailyCard();
print_r($tarot);

// ── Chinese Astrology ─────────────────────────────────────────────────
echo "\n=== Chinese Horoscope ===\n";
$chinese = $client->chinese()->getChineseHoroscope($birth);
print_r($chinese);

// ── Error Handling ────────────────────────────────────────────────────
echo "\n=== Error Handling Demo ===\n";
try {
    $result = $client->vedic()->getBirthDetails($birth);
} catch (QuotaExceededError $e) {
    echo "Quota exceeded (402): {$e->getMessage()}\n";
} catch (PlanRestrictedError $e) {
    echo "Plan restriction (403): {$e->getMessage()}\n";
} catch (AstrologyAPIException $e) {
    echo "API error ({$e->getHttpCode()}): {$e->getMessage()}\n";
}
