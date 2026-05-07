<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use AstrologyAPI\Client;
use AstrologyAPI\Models\BirthData;
use AstrologyAPI\Models\NumeroData;

/**
 * AstrologyAPI PHP SDK — Basic Usage Examples
 *
 * Before running:
 *   export ASTROLOGYAPI_API_KEY="your-api-key"
 *   composer install
 *   php examples/basic_usage.php
 */

// ── 1. Create the client ──────────────────────────────────────────────
$client = Client::fromEnv(); // reads ASTROLOGYAPI_API_KEY env var
// or: $client = new Client('your-api-key');

// ── 2. Build birth data ───────────────────────────────────────────────
$birth = new BirthData(
    day:   15,
    month: 8,
    year:  1990,
    hour:  14,
    min:   30,
    lat:   28.6139,
    lon:   77.2090,
    tzone: 5.5,
);

// ── 3. Vedic: Birth Details ───────────────────────────────────────────
echo "=== Birth Details ===\n";
$details = $client->vedic()->getBirthDetails($birth);
print_r($details);

// ── 4. Vedic: Planetary Positions ────────────────────────────────────
echo "\n=== Planets ===\n";
$planets = $client->vedic()->getPlanets($birth);
print_r($planets);

// ── 5. Vedic: Vimshottari Dasha ──────────────────────────────────────
echo "\n=== Current Vimshottari Dasha ===\n";
$dasha = $client->vedic()->getCurrentVdasha($birth);
print_r($dasha);

// ── 6. Vedic: Panchang ───────────────────────────────────────────────
echo "\n=== Basic Panchang ===\n";
$panchang = $client->vedic()->getBasicPanchang($birth);
print_r($panchang);

// ── 7. Vedic: Dosha Reports ───────────────────────────────────────────
echo "\n=== Kalsarpa Dosha ===\n";
$kalsarpa = $client->vedic()->getKalsarpaDosha($birth);
print_r($kalsarpa);

// ── 8. KP System ─────────────────────────────────────────────────────
echo "\n=== KP Planets ===\n";
$kpPlanets = $client->kp()->getPlanets($birth);
print_r($kpPlanets);

// ── 9. Vedic Numerology (needs full BirthData) ────────────────────────
echo "\n=== Vedic Numerology Report ===\n";
$vedNumer = $client->numerology()->getReport($birth);
print_r($vedNumer);

// ── 10. Western Numerology (only needs NumeroData) ───────────────────
echo "\n=== Western Life Path Number ===\n";
$numero = new NumeroData(day: 15, month: 8, year: 1990, name: 'John Doe');
$lifepath = $client->numerology()->getLifepathNumber($numero);
print_r($lifepath);

// ── 11. Western Astrology ────────────────────────────────────────────
echo "\n=== Western Planets ===\n";
$westernPlanets = $client->western()->getPlanets($birth);
print_r($westernPlanets);

// ── 12. Horoscopes ───────────────────────────────────────────────────
echo "\n=== Daily Horoscope (Aries) ===\n";
$horoscope = $client->horoscopes()->getDaily('aries');
print_r($horoscope);

// ── 13. Location ─────────────────────────────────────────────────────
echo "\n=== Geo Details ===\n";
$geo = $client->location()->getGeoDetails('New Delhi', 5);
print_r($geo);
