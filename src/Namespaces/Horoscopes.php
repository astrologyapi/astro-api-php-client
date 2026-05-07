<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

/**
 * Sun-sign horoscopes and nakshatra predictions.
 *
 * Most methods accept a zodiac sign name (e.g. "aries", "taurus") rather
 * than full birth data.
 */
class Horoscopes
{
    public function __construct(private HttpClient $http) {}

    /**
     * Get today's sun-sign prediction.
     *
     * @return array<string,mixed>
     */
    public function getDaily(string $zodiacName): array
    {
        return $this->http->post('sun_sign_prediction/daily/' . $zodiacName, []);
    }

    /**
     * Get tomorrow's sun-sign prediction.
     *
     * @return array<string,mixed>
     */
    public function getNext(string $zodiacName, ?float $timezone = null): array
    {
        return $this->http->post(
            'sun_sign_prediction/daily/next/' . $zodiacName,
            $timezone !== null ? ['timezone' => $timezone] : []
        );
    }

    /**
     * Get yesterday's sun-sign prediction.
     *
     * @return array<string,mixed>
     */
    public function getPrevious(string $zodiacName, ?float $timezone = null): array
    {
        return $this->http->post(
            'sun_sign_prediction/daily/previous/' . $zodiacName,
            $timezone !== null ? ['timezone' => $timezone] : []
        );
    }

    /**
     * Get today's consolidated sun-sign prediction (multiple life areas combined).
     *
     * @return array<string,mixed>
     */
    public function getDailyConsolidated(string $zodiacName, ?float $timezone = null): array
    {
        return $this->http->post(
            'sun_sign_consolidated/daily/' . $zodiacName,
            $timezone !== null ? ['timezone' => $timezone] : []
        );
    }

    /**
     * Get monthly sun-sign prediction.
     *
     * @return array<string,mixed>
     */
    public function getMonthly(string $zodiacName, ?float $timezone = null): array
    {
        return $this->http->post(
            'horoscope_prediction/monthly/' . $zodiacName,
            $timezone !== null ? ['timezone' => $timezone] : []
        );
    }

    /**
     * Get daily nakshatra prediction based on birth details.
     *
     * @return array<string,mixed>
     */
    public function getDailyNakshatraPrediction(BirthData $data): array
    {
        return $this->http->post('daily_nakshatra_prediction', $data->toArray());
    }
}
