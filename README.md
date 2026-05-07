# AstrologyAPI PHP SDK

Official PHP SDK for [AstrologyAPI.com](https://www.astrologyapi.com/) — providing programmatic access to Vedic, Western, KP, Lal Kitab, Numerology, Tarot, Chinese, and PDF report APIs.

## Requirements

- PHP 8.1+
- Guzzle 7.x (`guzzlehttp/guzzle`)

## Installation

```bash
composer require astrologyapi/astrologyapi-php
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use AstrologyAPI\Client;
use AstrologyAPI\Models\BirthData;

// Initialize client — reads ASTROLOGYAPI_API_KEY
$client = Client::fromEnv();

// Or provide your API key directly:
// $client = new Client('your-api-key');

// Build birth data
$birth = new BirthData(
    day: 15, month: 8, year: 1990,
    hour: 14, min: 30,
    lat: 28.6139,
    lon: 77.2090,
    tzone: 5.5,
);

// Get birth details
$details = $client->vedic()->getBirthDetails($birth);
print_r($details);
```

## Namespaces

The SDK is organized into 11 namespaces matching the API's logical boundaries:

| Namespace | Methods | Description |
|---|---|---|
| `$client->vedic()` | 53 | Vedic astrology: birth details, planets, dashas, panchang, matchmaking, doshas |
| `$client->kp()` | 6 | Krishnamurti Paddhati: planets, cusps, significators, horoscope |
| `$client->lalKitab()` | 5 | Lal Kitab: horoscope, debts, remedies, houses, planets |
| `$client->western()` | 28 | Western astrology: planets, aspects, solar return, personality reports, synastry |
| `$client->westernTransit()` | 5 | Western transit positions and forecast reports |
| `$client->numerology()` | 18 | Vedic numerology (BirthData) + Western numerology (NumeroData) |
| `$client->horoscopes()` | 6 | Daily/next/previous/consolidated/monthly sun-sign predictions + nakshatra prediction |
| `$client->tarot()` | 2 | Daily tarot card + detailed reading |
| `$client->chinese()` | 1 | Chinese horoscope |
| `$client->pdf()` | 8 | PDF report generation (Vedic + Western; returns binary string) |
| `$client->location()` | 2 | Geo details and timezone lookup |

## Usage Examples

### Vedic Astrology

```php
use AstrologyAPI\Models\BirthData;

$birth = new BirthData(day: 15, month: 8, year: 1990, hour: 14, min: 30, lat: 28.6139, lon: 77.2090, tzone: 5.5);

// Birth details
$client->vedic()->getBirthDetails($birth);

// Planets
$client->vedic()->getPlanets($birth);

// Current Vimshottari dasha
$client->vedic()->getCurrentVdasha($birth);

// Kundli chart (D1 by default)
$client->vedic()->getChart('D1', $birth);

// Panchang
$client->vedic()->getBasicPanchang($birth);
```

### Vedic Matchmaking

Male and female birth data are sent with `m_` and `f_` prefixes respectively.

```php
$male   = new BirthData(day: 10, month: 5, year: 1988, hour: 6, min: 0, lat: 28.61, lon: 77.20, tzone: 5.5);
$female = new BirthData(day: 22, month: 11, year: 1990, hour: 9, min: 15, lat: 19.07, lon: 72.87, tzone: 5.5);

$client->vedic()->getAshtakootPoints($male, $female);
$client->vedic()->getMatchMakingReport($male, $female);
```

### Western Astrology

```php
$client->western()->getPlanets($birth);
$client->western()->getPersonalityReport($birth);

// Synastry — person1 fields get p_ prefix, person2 get s_ prefix
$client->western()->getSynastry($person1, $person2);
$client->western()->getCompositeChart($person1, $person2);
```

### Numerology

Vedic numerology endpoints require full `BirthData`. Western numerology endpoints only need `NumeroData` (date + name).

```php
use AstrologyAPI\Models\NumeroData;

// Vedic numerology — needs full birth chart
$client->numerology()->getTable($birth);
$client->numerology()->getDailyPrediction($birth);

// Western numerology — only needs date + name
$numero = new NumeroData(day: 15, month: 8, year: 1990, name: 'John Doe');
$client->numerology()->getLifepathNumber($numero);
$client->numerology()->getExpressionNumber($numero);
```

### Horoscopes

```php
$client->horoscopes()->getDaily('aries');
$client->horoscopes()->getMonthly('scorpio');
$client->horoscopes()->getDailyConsolidated('capricorn');

// Nakshatra prediction needs birth data
$client->horoscopes()->getDailyNakshatraPrediction($birth);
```

### PDF Reports

All PDF methods return a **raw binary string** containing the PDF. Write it to a file or stream it to the browser.

```php
use AstrologyAPI\Models\PDFBranding;

$branding = new PDFBranding(
    companyName: 'My Astrology App',
    logoUrl: 'https://example.com/logo.png',
    companyEmail: 'hello@example.com',
);

// Vedic PDF reports
$pdf = $client->pdf()->vedic->getBasicHoroscope($birth, $branding);
file_put_contents('horoscope.pdf', $pdf);

$pdf = $client->pdf()->vedic->getProfessionalHoroscope($birth, $branding, ['name' => 'John']);

// Match making PDF
$pdf = $client->pdf()->vedic->getMatchMaking($male, $female, $branding, [
    'name'         => 'Rahul',
    'partner_name' => 'Priya',
]);

// Western PDF reports
$pdf = $client->pdf()->western->getNatalChart($birth, $branding);
$pdf = $client->pdf()->western->getSynastry($person1, $person2, $branding);

// Stream directly to browser
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="report.pdf"');
echo $pdf;
```

### Location

```php
// Geo lookup (city name → coordinates + timezone)
$client->location()->getGeoDetails('New Delhi', maxRows: 5);

// Timezone lookup (birth moment + coordinates → timezone and DST info)
$client->location()->getTimezone(new BirthData(
    day: 15,
    month: 8,
    year: 1990,
    hour: 14,
    min: 30,
    lat: 28.61,
    lon: 77.20,
    tzone: 5.5,
));
```

## Error Handling

```php
use AstrologyAPI\Errors\AuthenticationError;
use AstrologyAPI\Errors\PlanRestrictedError;
use AstrologyAPI\Errors\QuotaExceededError;
use AstrologyAPI\Errors\RateLimitError;
use AstrologyAPI\Errors\ValidationError;
use AstrologyAPI\Errors\ServerError;
use AstrologyAPI\Errors\AstrologyAPIException;

try {
    $result = $client->vedic()->getBirthDetails($birth);
} catch (AuthenticationError $e) {
    // 401 — Invalid or missing API key
} catch (QuotaExceededError $e) {
    // 402 — API quota exhausted; upgrade your plan
} catch (PlanRestrictedError $e) {
    // 403 — Endpoint not available on current plan
} catch (RateLimitError $e) {
    // 429 — Too many requests; slow down
} catch (ValidationError $e) {
    // 400/422 — Invalid request parameters
} catch (ServerError $e) {
    // 5xx — API server error
} catch (AstrologyAPIException $e) {
    // Any other API error
    echo $e->getHttpCode() . ': ' . $e->getMessage();
}
```

## Authentication

The SDK uses token-based authentication via the `x-astrologyapi-key` header.

```php
// Via environment variables: export ASTROLOGYAPI_API_KEY="ak-..."
$client = Client::fromEnv();

// Or direct instantiation:
$client = new Client('ak-your-token');
```

## Configuration

```php
$client = new Client('your-api-key', [
    'timeout' => 30.0,   // request timeout in seconds (default: 30)
    'retries' => 3,      // automatic retries on transient errors (default: 3)
]);
```

## Running Tests

```bash
composer install
composer test
```

## Static Analysis

```bash
composer analyse
```

## License

MIT
