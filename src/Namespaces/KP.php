<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

/**
 * KP (Krishnamurti Paddhati) system namespace.
 *
 * Provides methods for KP astrology calculations and chart analysis.
 */
class KP
{
    public function __construct(private HttpClient $http) {}

    /**
     * Get KP planetary positions and details.
     *
     * @return array<string,mixed>
     */
    public function getPlanets(BirthData $data): array
    {
        return $this->http->post('kp_planets', $data->toArray());
    }

    /**
     * Get KP house cusps.
     *
     * @return array<string,mixed>
     */
    public function getHouseCusps(BirthData $data): array
    {
        return $this->http->post('kp_house_cusps', $data->toArray());
    }

    /**
     * Get KP birth chart.
     *
     * @return array<string,mixed>
     */
    public function getBirthChart(BirthData $data): array
    {
        return $this->http->post('kp_birth_chart', $data->toArray());
    }

    /**
     * Get KP house significator.
     *
     * @return array<string,mixed>
     */
    public function getHouseSignificator(BirthData $data): array
    {
        return $this->http->post('kp_house_significator', $data->toArray());
    }

    /**
     * Get KP planet significator.
     *
     * @return array<string,mixed>
     */
    public function getPlanetSignificator(BirthData $data): array
    {
        return $this->http->post('kp_planet_significator', $data->toArray());
    }

    /**
     * Get KP horoscope.
     *
     * @return array<string,mixed>
     */
    public function getHoroscope(BirthData $data, bool $aspects = true, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('kp_horoscope', array_merge(
            $data->toArray(),
            [
                'aspects' => $aspects ? 'true' : 'false',
                'ayanamsha' => $ayanamsha,
            ]
        ));
    }
}
