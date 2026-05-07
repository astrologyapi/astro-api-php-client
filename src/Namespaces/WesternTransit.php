<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

/**
 * Western Transit astrology namespace.
 *
 * Provides current and forecast transits in the tropical (Western) zodiac.
 *
 * The shared Node/Postman reference exposes the live transit surface as:
 * - `tropical_transits/daily|weekly|monthly`
 * - `natal_transits/daily|weekly`
 *
 * These legacy PHP method names are kept as compatibility aliases that map to
 * those current live endpoints.
 */
class WesternTransit
{
    public function __construct(private HttpClient $http) {}

    /**
     * Get current transit planets in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getTransit(BirthData $data): array
    {
        return $this->http->post('tropical_transits/daily', $data->toArray());
    }

    /**
     * Get extended transit planets (includes outer planets and additional bodies).
     *
     * @return array<string,mixed>
     */
    public function getTransitExtended(BirthData $data): array
    {
        return $this->http->post('tropical_transits/weekly', $data->toArray());
    }

    /**
     * Get aspects between transit planets and natal chart.
     *
     * @return array<string,mixed>
     */
    public function getTransitAspects(BirthData $data): array
    {
        return $this->http->post('natal_transits/daily', $data->toArray());
    }

    /**
     * Get transit forecast report.
     *
     * @return array<string,mixed>
     */
    public function getTransitForecast(BirthData $data): array
    {
        return $this->http->post('tropical_transits/monthly', $data->toArray());
    }

    /**
     * Get detailed transit interpretation report.
     *
     * @return array<string,mixed>
     */
    public function getTransitReport(BirthData $data): array
    {
        return $this->http->post('natal_transits/weekly', $data->toArray());
    }
}
