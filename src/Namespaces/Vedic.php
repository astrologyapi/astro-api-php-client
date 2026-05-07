<?php
declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

class Vedic
{
    public function __construct(private HttpClient $http) {}

    // ── Birth Data ──────────────────────────────
    /** @return array<string,mixed> */
    public function getBirthDetails(BirthData $data): array { return $this->http->post('birth_details', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getAstroDetails(BirthData $data): array { return $this->http->post('astro_details', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getAyanamsha(BirthData $data): array { return $this->http->post('ayanamsha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getGhatChakra(BirthData $data): array { return $this->http->post('ghat_chakra', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getBhavMadhya(BirthData $data): array { return $this->http->post('bhav_madhya', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPlanetNature(BirthData $data): array { return $this->http->post('planet_nature', $data->toArray()); }
    // ── Planets ─────────────────────────────────
    /** @return array<string,mixed> */
    public function getPlanets(BirthData $data): array { return $this->http->post('planets', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getExtendedPlanets(BirthData $data): array { return $this->http->post('planets/extended', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPlanetAshtak(string $planetName, BirthData $data): array { return $this->http->post('planet_ashtak/' . $planetName, $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSarvashtak(BirthData $data): array { return $this->http->post('sarvashtak', $data->toArray()); }

    // ── Charts ──────────────────────────────────
    /** @return array<string,mixed> */
    public function getChart(string $chartId, BirthData $data): array { return $this->http->post('horo_chart/' . $chartId, $data->toArray()); }
    /** @return array<string,mixed> */
    public function getChartImage(string $chartId, BirthData $data): array { return $this->http->post('horo_chart_image/' . $chartId, $data->toArray()); }
    /** @return array<string,mixed> */
    public function getExtendedChart(BirthData $data, string $chartId = 'D1'): array
    {
        return $this->http->post(
            'horo_chart_extended/' . $chartId,
            array_merge($data->toArray(), ['ayanamsha' => 'LAHIRI'])
        );
    }
    // ── Vimshottari Dasha ───────────────────────
    /** @return array<string,mixed> */
    public function getCurrentVdasha(BirthData $data): array { return $this->http->post('current_vdasha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getCurrentVdashaAll(BirthData $data): array { return $this->http->post('current_vdasha_all', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getMajorVdasha(BirthData $data): array { return $this->http->post('major_vdasha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSubVdasha(string $md, BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('sub_vdasha/' . $md, array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]));
    }
    /** @return array<string,mixed> */
    public function getSubSubVdasha(string $md, string $ad, BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('sub_sub_vdasha/' . $md . '/' . $ad, array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]));
    }
    /** @return array<string,mixed> */
    public function getSubSubSubVdasha(string $md, string $ad, string $pd, BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('sub_sub_sub_vdasha/' . $md . '/' . $ad . '/' . $pd, array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]));
    }
    /** @return array<string,mixed> */
    public function getSubSubSubSubVdasha(string $md, string $ad, string $pd, string $sd, BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('sub_sub_sub_sub_vdasha/' . $md . '/' . $ad . '/' . $pd . '/' . $sd, array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]));
    }

    // ── Char Dasha ──────────────────────────────
    /** @return array<string,mixed> */
    public function getCurrentCharDasha(BirthData $data): array { return $this->http->post('current_chardasha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getCurrentCharDashaSub(BirthData $data): array
    {
        return $this->getCurrentCharDasha($data);
    }
    /** @return array<string,mixed> */
    public function getMajorCharDasha(BirthData $data): array { return $this->http->post('major_chardasha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSubCharDasha(string $md, BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post(
            'sub_chardasha/' . $md,
            array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]),
            ['encoding' => 'form-urlencoded']
        );
    }

    // ── Yogini Dasha ────────────────────────────
    /** @return array<string,mixed> */
    public function getCurrentYoginiDasha(BirthData $data): array { return $this->http->post('current_yogini_dasha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getCurrentYoginiDashaSub(BirthData $data): array
    {
        return $this->getCurrentYoginiDasha($data);
    }
    /** @return array<string,mixed> */
    public function getMajorYoginiDasha(BirthData $data): array { return $this->http->post('major_yogini_dasha', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSubYoginiDasha(BirthData $data): array { return $this->http->post('sub_yogini_dasha', $data->toArray()); }

    // ── Doshas & Remedies ───────────────────────
    /** @return array<string,mixed> */
    public function getKalsarpaDosha(BirthData $data): array { return $this->http->post('kalsarpa_details', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSadhesatiStatus(BirthData $data): array { return $this->http->post('sadhesati_current_status', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSadhesatiLifeDetails(BirthData $data): array { return $this->http->post('sadhesati_life_details', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPitraDosha(BirthData $data): array { return $this->http->post('pitra_dosha_report', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getGemSuggestion(BirthData $data): array { return $this->http->post('basic_gem_suggestion', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPujaSuggestion(BirthData $data): array { return $this->http->post('puja_suggestion', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getRudrakshaSuggestion(BirthData $data): array { return $this->http->post('rudraksha_suggestion', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getSadhesatiRemedies(BirthData $data): array { return $this->http->post('sadhesati_remedies', $data->toArray()); }

    // ── Panchang ────────────────────────────────
    /** @return array<string,mixed> */
    public function getBasicPanchang(BirthData $data): array { return $this->http->post('basic_panchang', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getBasicPanchangSunrise(BirthData $data): array { return $this->http->post('basic_panchang/sunrise', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getAdvancedPanchang(BirthData $data): array { return $this->http->post('advanced_panchang', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getAdvancedPanchangSunrise(BirthData $data): array { return $this->http->post('advanced_panchang/sunrise', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPlanetPanchang(BirthData $data): array { return $this->http->post('planet_panchang', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPlanetPanchangSunrise(BirthData $data): array { return $this->http->post('planet_panchang/sunrise', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPanchangChartSunrise(BirthData $data): array { return $this->http->post('panchang_chart/sunrise', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getTamilMonthPanchang(BirthData $data): array { return $this->http->post('tamil_month_panchang', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getTamilPanchang(BirthData $data): array { return $this->http->post('tamil_panchang', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPanchangFestival(BirthData $data): array { return $this->http->post('panchang_festival', $data->toArray()); }

    // ── Muhurta ─────────────────────────────────
    /** @return array<string,mixed> */
    public function getHoraMuhurta(BirthData $data): array { return $this->http->post('hora_muhurta', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getHoraMuhurtaDinman(BirthData $data): array { return $this->http->post('hora_muhurta_dinman', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getChaughadiyaMuhurta(BirthData $data): array { return $this->http->post('chaughadiya_muhurta', $data->toArray()); }

    // ── Matchmaking ─────────────────────────────
    /** @return array<string,mixed> */
    public function getMatchBirthDetails(BirthData $male, BirthData $female): array {
        return $this->http->post('match_birth_details', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchObstructions(BirthData $male, BirthData $female): array {
        return $this->http->post('match_obstructions', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchAstroDetails(BirthData $male, BirthData $female): array {
        return $this->http->post('match_astro_details', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchPlanetDetails(BirthData $male, BirthData $female): array {
        return $this->http->post('match_planet_details', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchManglikReport(BirthData $male, BirthData $female): array {
        return $this->http->post('match_manglik_report', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getAshtakootPoints(BirthData $male, BirthData $female): array {
        return $this->http->post('match_ashtakoot_points', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getDashakootPoints(BirthData $male, BirthData $female): array {
        return $this->http->post('match_dashakoot_points', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchPercentage(BirthData $male, BirthData $female): array {
        return $this->http->post('match_percentage', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchMakingReport(BirthData $male, BirthData $female): array {
        return $this->http->post('match_making_report', $this->mergeMatchData($male, $female));
    }
    /** @return array<string,mixed> */
    public function getMatchMakingDetailedReport(BirthData $male, BirthData $female): array {
        return $this->http->post('match_making_detailed_report', $this->mergeMatchData($male, $female));
    }

    // ── Varshaphal ──────────────────────────────
    /** @return array<string,mixed> */
    public function getVarshaphalDetails(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_details', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalYearChart(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_year_chart', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalMonthChart(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_month_chart', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalPlanets(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_planets', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return mixed */
    public function getVarshaphalMuntha(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): mixed
    {
        return $this->http->post('varshaphal_muntha', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalMuddaDasha(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_mudda_dasha', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalPanchavargeeyaBala(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_panchavargeeya_bala', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalHarshaBala(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_harsha_bala', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalYoga(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_yoga', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getVarshaphalSahamPoints(BirthData|array $data, ?int $varshaphalYear = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('varshaphal_saham_points', $this->normaliseVarshaphalData($data, $varshaphalYear, $ayanamsha));
    }

    // ── Reports ─────────────────────────────────
    /** @return array<string,mixed> */
    public function getGeneralAscendantReport(BirthData $data, ?string $gender = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('general_ascendant_report', $this->withReportFields($data, $gender, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getNakshatraReport(BirthData $data, ?string $gender = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post('general_nakshatra_report', $this->withReportFields($data, $gender, $ayanamsha));
    }
    /** @return array<string,mixed> */
    public function getGeneralHouseReport(string $planetName, BirthData $data): array { return $this->http->post('general_house_report/' . $planetName, $data->toArray()); }
    /** @return array<string,mixed> */
    public function getRashiReport(string $planetName, BirthData $data): array { return $this->http->post('general_rashi_report/' . $planetName, $data->toArray()); }
    /** @return array<string,mixed> */
    public function getPersonalCharacteristics(BirthData $data, ?string $gender = null, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->getGeneralAscendantReport($data, $gender, $ayanamsha);
    }

    // ── Biorhythm ───────────────────────────────
    /** @return array<string,mixed> */
    public function getBiorhythm(BirthData $data): array { return $this->http->post('biorhythm', $data->toArray()); }
    /** @return array<string,mixed> */
    public function getMoonBiorhythm(BirthData $data): array { return $this->http->post('moon_biorhythm', $data->toArray()); }

    // ── Helper ──────────────────────────────────

    /**
     * Flatten male + female BirthData into a single request body.
     * Male fields are prefixed m_, female fields are prefixed f_.
     *
     * @return array<string,mixed>
     */
    private function mergeMatchData(BirthData $male, BirthData $female): array
    {
        $params = [];
        foreach ($male->toArray() as $key => $val) {
            $params['m_' . $key] = $val;
        }
        foreach ($female->toArray() as $key => $val) {
            $params['f_' . $key] = $val;
        }
        return $params;
    }

    /**
     * @return array<string,mixed>
     */
    private function normaliseVarshaphalData(BirthData|array $data, ?int $varshaphalYear, string $ayanamsha): array
    {
        $body = $data instanceof BirthData ? $data->toArray() : $data;

        if (!isset($body['varshaphal_year']) && isset($body['year_count'], $body['year'])) {
            $body['varshaphal_year'] = (int) $body['year'] + (int) $body['year_count'];
        }

        if ($varshaphalYear !== null) {
            $body['varshaphal_year'] = $varshaphalYear;
        }

        if (!isset($body['varshaphal_year'])) {
            throw new \InvalidArgumentException('Varshaphal endpoints require varshaphal_year or year_count.');
        }

        unset($body['year_count'], $body['name'], $body['place'], $body['gender']);
        $body['ayanamsha'] = $body['ayanamsha'] ?? $ayanamsha;

        return [
            'day' => (int) ($body['day'] ?? 0),
            'month' => (int) ($body['month'] ?? 0),
            'year' => (int) ($body['year'] ?? 0),
            'hour' => (int) ($body['hour'] ?? 0),
            'min' => (int) ($body['min'] ?? 0),
            'lat' => (float) ($body['lat'] ?? 0.0),
            'lon' => (float) ($body['lon'] ?? 0.0),
            'tzone' => (float) ($body['tzone'] ?? 0.0),
            'varshaphal_year' => (int) $body['varshaphal_year'],
            'ayanamsha' => (string) $body['ayanamsha'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function withReportFields(BirthData $data, ?string $gender, string $ayanamsha): array
    {
        $body = array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]);
        if ($gender !== null && $gender !== '') {
            $body['gender'] = $gender;
        }

        return $body;
    }
}
