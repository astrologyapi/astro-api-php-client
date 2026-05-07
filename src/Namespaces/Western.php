<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

/**
 * Western astrology namespace providing tropical zodiac calculations and reports.
 */
class Western
{
    public function __construct(
        private HttpClient $http,
    ) {
    }

    // ── Core Western ──────────────────────────────────────────────────────

    /**
     * Get planetary positions in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getPlanets(BirthData $data): array
    {
        return $this->http->post('planets/tropical', $data->toArray());
    }

    /**
     * Get house cusps using tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getHouseCusps(BirthData $data): array
    {
        return $this->http->post('house_cusps/tropical', $data->toArray());
    }

    /**
     * Get house cusps report in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getHouseCuspsReport(BirthData $data): array
    {
        return $this->http->post('house_cusps_report/tropical', $data->toArray());
    }

    /**
     * Get natal house cusp report.
     *
     * @return array<string,mixed>
     */
    public function getNatalHouseCuspReport(BirthData $data): array
    {
        return $this->http->post('natal_house_cusp_report', $data->toArray());
    }

    /**
     * Get western horoscope chart.
     *
     * @return array<string,mixed>
     */
    public function getWesternHoroscope(BirthData $data): array
    {
        return $this->http->post('western_horoscope', $data->toArray());
    }

    /**
     * Get planetary aspects.
     *
     * The shared Postman surface no longer exposes a standalone `aspects`
     * endpoint; `western_horoscope` is the live Western chart endpoint that
     * still returns the aspects block.
     *
     * @return array<string,mixed>
     */
    public function getAspects(BirthData $data): array
    {
        return $this->http->post('western_horoscope', $data->toArray());
    }

    /**
     * Get sun sign information.
     *
     * The current shared collections expose this through the Western sign
     * report endpoint for the Sun rather than a standalone `sun_sign` path.
     *
     * @return array<string,mixed>
     */
    public function getSunSign(BirthData $data): array
    {
        return $this->http->post('general_sign_report/tropical/sun', $data->toArray());
    }

    /**
     * Get natal wheel chart.
     *
     * @return array<string,mixed>
     */
    public function getNatalWheelChart(BirthData $data): array
    {
        return $this->http->post('natal_wheel_chart', $data->toArray());
    }

    /**
     * Get natal chart interpretation.
     *
     * @return array<string,mixed>
     */
    public function getNatalInterpretation(BirthData $data): array
    {
        return $this->http->post('natal_chart_interpretation', $data->toArray());
    }

    /**
     * Get western chart data.
     *
     * @return array<string,mixed>
     */
    public function getWesternChartData(BirthData $data): array
    {
        return $this->http->post('western_chart_data', $data->toArray());
    }

    // ── Reports with planet name parameter ────────────────────────────────

    /**
     * Get general sign report for a specific planet.
     *
     * @return array<string,mixed>
     */
    public function getGeneralSignReport(string $planetName, BirthData $data): array
    {
        return $this->http->post('general_sign_report/tropical/' . $planetName, $data->toArray());
    }

    /**
     * Get general house report for a specific planet.
     *
     * @return array<string,mixed>
     */
    public function getGeneralHouseReport(string $planetName, BirthData $data): array
    {
        return $this->http->post('general_house_report/tropical/' . $planetName, $data->toArray());
    }

    // ── Moon ──────────────────────────────────────────────────────────────

    /**
     * Get moon phase report.
     *
     * @return array<string,mixed>
     */
    public function getMoonPhase(BirthData $data): array
    {
        return $this->http->post('moon_phase_report', $data->toArray());
    }

    /**
     * Get lunar metrics.
     *
     * @return array<string,mixed>
     */
    public function getLunarMetrics(BirthData $data): array
    {
        return $this->http->post('lunar_metrics', $data->toArray());
    }

    // ── Solar Return ──────────────────────────────────────────────────────

    /**
     * Get solar return details.
     *
     * @return array<string,mixed>
     */
    public function getSolarReturnDetails(BirthData $data, ?int $solarYear = null): array
    {
        return $this->http->post('solar_return_details', $this->withOptionalField($data->toArray(), 'solar_year', $solarYear));
    }

    /**
     * Get solar return planets.
     *
     * @return array<string,mixed>
     */
    public function getSolarReturnPlanets(BirthData $data, ?int $solarYear = null): array
    {
        return $this->http->post('solar_return_planets', $this->withOptionalField($data->toArray(), 'solar_year', $solarYear));
    }

    /**
     * Get solar return house cusps.
     *
     * @return array<string,mixed>
     */
    public function getSolarReturnHouseCusps(BirthData $data, ?int $solarYear = null): array
    {
        return $this->http->post('solar_return_house_cusps', $this->withOptionalField($data->toArray(), 'solar_year', $solarYear));
    }

    /**
     * Get solar return planet report.
     *
     * @return array<string,mixed>
     */
    public function getSolarReturnPlanetReport(BirthData $data, ?int $solarYear = null): array
    {
        return $this->http->post('solar_return_planet_report', $this->withOptionalField($data->toArray(), 'solar_year', $solarYear));
    }

    /**
     * Get solar return planet aspects.
     *
     * @return array<string,mixed>
     */
    public function getSolarReturnPlanetAspects(BirthData $data, ?int $solarYear = null): array
    {
        return $this->http->post('solar_return_planet_aspects', $this->withOptionalField($data->toArray(), 'solar_year', $solarYear));
    }

    /**
     * Get solar return aspects report.
     *
     * @return array<string,mixed>
     */
    public function getSolarReturnAspectsReport(BirthData $data, ?int $solarYear = null): array
    {
        return $this->http->post('solar_return_aspects_report', $this->withOptionalField($data->toArray(), 'solar_year', $solarYear));
    }

    // ── Personality Reports ───────────────────────────────────────────────

    /**
     * Get personality report in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getPersonalityReport(BirthData $data): array
    {
        return $this->http->post('personality_report/tropical', $data->toArray());
    }

    /**
     * Get romantic personality report in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getRomanticPersonalityReport(BirthData $data): array
    {
        return $this->http->post('romantic_personality_report/tropical', $data->toArray());
    }

    /**
     * Get friendship report in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getFriendshipReport(BirthData $person1, BirthData $person2): array
    {
        return $this->http->post('friendship_report/tropical', $this->mergeBirthData($person1, $person2));
    }

    /**
     * Get romantic forecast report in tropical zodiac.
     *
     * @return array<string,mixed>
     */
    public function getRomanticForecastReport(BirthData $data): array
    {
        return $this->http->post('romantic_forecast_report/tropical', $data->toArray());
    }

    /**
     * Get karma/destiny report.
     *
     * @return array<string,mixed>
     */
    public function getKarmaDestinyReport(BirthData $person1, BirthData $person2): array
    {
        return $this->http->post('karma_destiny_report/tropical', $this->mergeBirthData($person1, $person2));
    }

    // ── Compatibility ─────────────────────────────────────────────────────

    /**
     * Get zodiac compatibility between two signs.
     *
     * @return array<string,mixed>
     */
    public function getZodiacCompatibility(string $zodiacName, string $partnerZodiacName): array
    {
        return $this->http->post('zodiac_compatibility/' . $zodiacName . '/' . $partnerZodiacName, []);
    }

    /**
     * Get synastry horoscope between two people.
     *
     * @return array<string,mixed>
     */
    public function getSynastry(BirthData $person1, BirthData $person2): array
    {
        $data = $this->mergeBirthData($person1, $person2);

        return $this->http->post('synastry_horoscope', $data);
    }

    /**
     * Get composite chart for two people.
     *
     * @return array<string,mixed>
     */
    public function getCompositeChart(BirthData $person1, BirthData $person2): array
    {
        $data = $this->mergeBirthData($person1, $person2);

        return $this->http->post('composite_horoscope', $data);
    }

    /**
     * Flatten two BirthData objects into a single request body.
     * Person 1 (primary) fields are prefixed p_, person 2 (secondary) with s_.
     *
     * @return array<string,mixed>
     */
    private function mergeBirthData(BirthData $person1, BirthData $person2): array
    {
        $merged = [];

        foreach ($person1->toArray() as $key => $value) {
            $merged['p_' . $key] = $value;
        }
        foreach ($person2->toArray() as $key => $value) {
            $merged['s_' . $key] = $value;
        }

        return $merged;
    }

    /**
     * @param array<string,mixed> $body
     * @param scalar|null $value
     * @return array<string,mixed>
     */
    private function withOptionalField(array $body, string $field, string|int|float|bool|null $value): array
    {
        if ($value === null) {
            return $body;
        }

        $body[$field] = $value;
        return $body;
    }
}
