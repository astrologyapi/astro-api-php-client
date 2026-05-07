<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;
use AstrologyAPI\Models\NumeroData;

/**
 * Numerology namespace — Vedic and Western numerology calculations.
 *
 * Vedic numerology methods accept BirthData (requires full birth chart data).
 * Western numerology methods accept NumeroData (requires only date + name).
 */
class Numerology
{
    public function __construct(private HttpClient $http) {}

    // ── Vedic Numerology ──────────────────────────────────────────────────────

    /**
     * Get Vedic numerology table.
     *
     * @return array<string,mixed>
     */
    public function getTable(BirthData $data): array
    {
        return $this->http->post('numero_table', $data->toArray());
    }

    /**
     * Get Vedic numerology report.
     *
     * @return array<string,mixed>
     */
    public function getReport(BirthData $data): array
    {
        return $this->http->post('numero_report', $data->toArray());
    }

    /**
     * Get Vedic favourable time periods.
     *
     * @return array<string,mixed>
     */
    public function getFavTime(BirthData $data): array
    {
        return $this->http->post('numero_fav_time', $data->toArray());
    }

    /**
     * Get Vedic place vastu analysis.
     *
     * @return array<string,mixed>
     */
    public function getPlaceVastu(BirthData $data): array
    {
        return $this->http->post('numero_place_vastu', $data->toArray());
    }

    /**
     * Get Vedic fasts report.
     *
     * @return array<string,mixed>
     */
    public function getFastsReport(BirthData $data): array
    {
        return $this->http->post('numero_fasts_report', $data->toArray());
    }

    /**
     * Get Vedic favourable lord.
     *
     * @return array<string,mixed>
     */
    public function getFavLord(BirthData $data): array
    {
        return $this->http->post('numero_fav_lord', $data->toArray());
    }

    /**
     * Get Vedic favourable mantra.
     *
     * @return array<string,mixed>
     */
    public function getFavMantra(BirthData $data): array
    {
        return $this->http->post('numero_fav_mantra', $data->toArray());
    }

    /**
     * Get Vedic numerology daily prediction.
     *
     * @return array<string,mixed>
     */
    public function getDailyPrediction(BirthData $data): array
    {
        return $this->http->post('numero_prediction/daily', $data->toArray());
    }

    // ── Western Numerology ────────────────────────────────────────────────────

    /**
     * Get all Western numerological numbers.
     *
     * @return array<string,mixed>
     */
    public function getNumerologicalNumbers(NumeroData $data): array
    {
        return $this->postWesternNumerology('numerological_numbers', $data);
    }

    /**
     * Get Western life path number.
     *
     * @return array<string,mixed>
     */
    public function getLifepathNumber(NumeroData $data): array
    {
        return $this->postWesternNumerology('lifepath_number', $data);
    }

    /**
     * Get Western personality number.
     *
     * @return array<string,mixed>
     */
    public function getPersonalityNumber(NumeroData $data): array
    {
        return $this->postWesternNumerology('personality_number', $data);
    }

    /**
     * Get Western expression number.
     *
     * @return array<string,mixed>
     */
    public function getExpressionNumber(NumeroData $data): array
    {
        return $this->postWesternNumerology('expression_number', $data);
    }

    /**
     * Get Western soul urge number.
     *
     * @return array<string,mixed>
     */
    public function getSoulUrgeNumber(NumeroData $data): array
    {
        return $this->postWesternNumerology('soul_urge_number', $data);
    }

    /**
     * Get Western challenge numbers.
     *
     * @return array<string,mixed>
     */
    public function getChallengeNumbers(NumeroData $data): array
    {
        return $this->postWesternNumerology('challenge_numbers', $data);
    }

    /**
     * Get Western subconscious self number.
     *
     * @return array<string,mixed>
     */
    public function getSubConsciousSelfNumber(NumeroData $data): array
    {
        return $this->postWesternNumerology('sub_conscious_self_number', $data);
    }

    /**
     * Get Western personal day prediction.
     *
     * @return array<string,mixed>
     */
    public function getPersonalDay(NumeroData $data): array
    {
        return $this->postWesternNumerology('personal_day_prediction', $data);
    }

    /**
     * Get Western personal month prediction.
     *
     * @return array<string,mixed>
     */
    public function getPersonalMonth(NumeroData $data): array
    {
        return $this->postWesternNumerology('personal_month_prediction', $data);
    }

    /**
     * Get Western personal year prediction.
     *
     * @return array<string,mixed>
     */
    public function getPersonalYear(NumeroData $data): array
    {
        return $this->postWesternNumerology('personal_year_prediction', $data);
    }

    /**
     * Western numerology endpoints are name-sensitive, so mirror the shared
     * cross-SDK request fields and keep the body urlencoded like Postman.
     *
     * @return array<string,mixed>
     */
    private function postWesternNumerology(string $endpoint, NumeroData $data): array
    {
        return $this->http->post(
            $endpoint,
            $this->buildWesternNumerologyPayload($data),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function buildWesternNumerologyPayload(NumeroData $data): array
    {
        $body = [
            'day' => $data->day,
            'month' => $data->month,
            'year' => $data->year,
            'date' => $data->day,
        ];

        if ($data->name !== '') {
            $body['name'] = $data->name;
            $body['full_name'] = $data->name;
            $body['full name'] = $data->name;
        }

        return $body;
    }
}
