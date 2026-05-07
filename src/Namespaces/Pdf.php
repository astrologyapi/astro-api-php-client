<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;
use AstrologyAPI\Models\PDFBranding;

/**
 * PDF namespace — generate branded PDF astrology reports.
 *
 * All methods return a raw binary string containing the PDF file.
 * Write the result to disk or stream it directly to the client.
 *
 * Example:
 *   $pdf = $api->pdf()->vedic->getBasicHoroscope($birthData);
 *   file_put_contents('horoscope.pdf', $pdf);
 *
 *   // Stream to browser:
 *   header('Content-Type: application/pdf');
 *   header('Content-Disposition: attachment; filename="horoscope.pdf"');
 *   echo $pdf;
 */
class Pdf
{
    /** Vedic PDF reports (Kundli, match-making, etc.) */
    public readonly VedicPDF $vedic;

    /** Western PDF reports (natal chart, synastry, etc.) */
    public readonly WesternPDF $western;

    public function __construct(HttpClient $http)
    {
        $this->vedic   = new VedicPDF($http);
        $this->western = new WesternPDF($http);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Vedic PDF Reports
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Vedic PDF report generation.
 */
class VedicPDF
{
    use PdfBodyBuilders;

    public function __construct(private HttpClient $http) {}

    /**
     * Mini Kundli PDF — compact birth chart summary (1–2 pages).
     *
     * @param array{name?: string, place?: string} $extra Optional name/place to print on the report.
     */
    public function getMiniKundli(BirthData $data, ?PDFBranding $branding = null, array $extra = []): string
    {
        return $this->http->postBinary(
            'mini_horoscope_pdf',
            self::buildVedicSingleReportBody($data, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * Basic horoscope PDF — standard birth chart with planetary positions and dasha table.
     *
     * @param array{name?: string, place?: string} $extra
     */
    public function getBasicHoroscope(BirthData $data, ?PDFBranding $branding = null, array $extra = []): string
    {
        return $this->http->postBinary(
            'basic_horoscope_pdf',
            self::buildVedicSingleReportBody($data, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * Professional horoscope PDF — comprehensive report with charts, dashas, doshas, and interpretations.
     *
     * @param array{name?: string, place?: string} $extra
     */
    public function getProfessionalHoroscope(BirthData $data, ?PDFBranding $branding = null, array $extra = []): string
    {
        return $this->http->postBinary(
            'pro_horoscope_pdf',
            self::buildVedicSingleReportBody($data, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * Match Making PDF — compatibility report for two individuals.
     *
     * @param array{name?: string, partner_name?: string, place?: string} $extra
     */
    public function getMatchMaking(
        BirthData $male,
        BirthData $female,
        ?PDFBranding $branding = null,
        array $extra = [],
    ): string {
        return $this->http->postBinary(
            'match_making_pdf',
            self::buildVedicMatchMakingBody($male, $female, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @param  array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private static function buildVedicSingleReportBody(BirthData $data, ?PDFBranding $branding, array $extra): array
    {
        $body = [
            'day' => $data->day,
            'month' => $data->month,
            'year' => $data->year,
            'hour' => $data->hour,
            'min' => $data->min,
            'lat' => $data->lat,
            'lon' => $data->lon,
            'tzone' => $data->tzone,
            'gender' => $extra['gender'] ?? null,
            'place' => $extra['place'] ?? null,
            'language' => $extra['language'] ?? 'en',
        ];

        return self::compactBody(array_merge(
            $body,
            self::flattenReportBranding($branding),
            self::flattenChartStyle($branding)
        ));
    }

    /**
     * @param  array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private static function buildVedicMatchMakingBody(BirthData $male, BirthData $female, ?PDFBranding $branding, array $extra): array
    {
        $maleName = self::splitFullName((string) ($extra['name'] ?? ''));
        $femaleName = self::splitFullName((string) ($extra['partner_name'] ?? ''));

        return self::compactBody(array_merge([
            'm_first_name' => $extra['m_first_name'] ?? $maleName['first_name'] ?? null,
            'm_last_name' => $extra['m_last_name'] ?? $maleName['last_name'] ?? null,
            'm_day' => $male->day,
            'm_month' => $male->month,
            'm_year' => $male->year,
            'm_hour' => $male->hour,
            'm_minute' => $male->min,
            'm_latitude' => $male->lat,
            'm_longitude' => $male->lon,
            'm_timezone' => $male->tzone,
            'm_place' => $extra['m_place'] ?? $extra['place'] ?? null,
            'f_first_name' => $extra['f_first_name'] ?? $femaleName['first_name'] ?? null,
            'f_last_name' => $extra['f_last_name'] ?? $femaleName['last_name'] ?? null,
            'f_day' => $female->day,
            'f_month' => $female->month,
            'f_year' => $female->year,
            'f_hour' => $female->hour,
            'f_minute' => $female->min,
            'f_latitude' => $female->lat,
            'f_longitude' => $female->lon,
            'f_timezone' => $female->tzone,
            'f_place' => $extra['f_place'] ?? $extra['place'] ?? null,
            'language' => $extra['language'] ?? 'en',
            'ashtakoot' => $extra['ashtakoot'] ?? 'true',
            'papasyam' => $extra['papasyam'] ?? 'true',
            'dashakoot' => $extra['dashakoot'] ?? 'true',
        ], self::flattenMatchMakingBranding($branding), self::flattenChartStyle($branding)));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Western PDF Reports
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Western PDF report generation.
 */
class WesternPDF
{
    use PdfBodyBuilders;

    public function __construct(private HttpClient $http) {}

    /**
     * Western natal chart PDF — birth chart with aspects, house positions, and interpretation.
     *
     * @param array{name?: string, place?: string} $extra
     */
    public function getNatalChart(BirthData $data, ?PDFBranding $branding = null, array $extra = []): string
    {
        return $this->http->postBinary(
            'natal_horoscope_report/tropical',
            self::buildWesternSingleReportBody($data, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * Life forecast PDF — transit-based annual forecast report.
     *
     * @param array{name?: string, place?: string} $extra
     */
    public function getLifeForecast(BirthData $data, ?PDFBranding $branding = null, array $extra = []): string
    {
        return $this->http->postBinary(
            'life_forecast_report/tropical',
            self::buildWesternSingleReportBody($data, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * Solar Return PDF — annual solar return chart and interpretation.
     *
     * @param array{name?: string, place?: string} $extra
     */
    public function getSolarReturn(BirthData $data, ?PDFBranding $branding = null, array $extra = []): string
    {
        return $this->http->postBinary(
            'solar_return_report/tropical',
            self::buildWesternSingleReportBody($data, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * Synastry couple PDF — relationship compatibility report for two people.
     * Person 1 fields are prefixed p_, person 2 fields are prefixed s_.
     *
     * @param array{name?: string, partner_name?: string, place?: string} $extra
     */
    public function getSynastry(
        BirthData $person1,
        BirthData $person2,
        ?PDFBranding $branding = null,
        array $extra = [],
    ): string {
        return $this->http->postBinary(
            'synastry_couple_report/tropical',
            self::buildWesternSynastryBody($person1, $person2, $branding, $extra),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @param  array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private static function buildWesternSingleReportBody(BirthData $data, ?PDFBranding $branding, array $extra): array
    {
        return self::compactBody(array_merge([
            'name' => $extra['name'] ?? null,
            'day' => $data->day,
            'month' => $data->month,
            'year' => $data->year,
            'hour' => $data->hour,
            'minute' => $data->min,
            'latitude' => $data->lat,
            'longitude' => $data->lon,
            'timezone' => $data->tzone,
            'place' => $extra['place'] ?? null,
            'solar_year' => $extra['solar_year'] ?? null,
            'language' => $extra['language'] ?? 'en',
        ], self::flattenReportBranding($branding)));
    }

    /**
     * @param  array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private static function buildWesternSynastryBody(BirthData $person1, BirthData $person2, ?PDFBranding $branding, array $extra): array
    {
        $primaryName = self::splitFullName((string) ($extra['name'] ?? ''));
        $secondaryName = self::splitFullName((string) ($extra['partner_name'] ?? ''));

        return self::compactBody(array_merge([
            'p_first_name' => $extra['p_first_name'] ?? $primaryName['first_name'] ?? null,
            'p_last_name' => $extra['p_last_name'] ?? $primaryName['last_name'] ?? null,
            'p_day' => $person1->day,
            'p_month' => $person1->month,
            'p_year' => $person1->year,
            'p_hour' => $person1->hour,
            'p_minute' => $person1->min,
            'p_latitude' => $person1->lat,
            'p_longitude' => $person1->lon,
            'p_timezone' => $person1->tzone,
            'p_place' => $extra['p_place'] ?? $extra['place'] ?? null,
            's_first_name' => $extra['s_first_name'] ?? $secondaryName['first_name'] ?? null,
            's_last_name' => $extra['s_last_name'] ?? $secondaryName['last_name'] ?? null,
            's_day' => $person2->day,
            's_month' => $person2->month,
            's_year' => $person2->year,
            's_hour' => $person2->hour,
            's_minute' => $person2->min,
            's_latitude' => $person2->lat,
            's_longitude' => $person2->lon,
            's_timezone' => $person2->tzone,
            's_place' => $extra['s_place'] ?? $extra['place'] ?? null,
            'language' => $extra['language'] ?? 'en',
        ], self::flattenReportBranding($branding)));
    }
}

trait PdfBodyBuilders
{
    /**
     * @return array<string,mixed>
     */
    private static function flattenReportBranding(?PDFBranding $branding): array
    {
        if ($branding === null) {
            return [];
        }

        return self::compactBody([
            'logo_url' => $branding->logoUrl,
            'company_name' => $branding->companyName,
            'company_info' => $branding->companyInfo,
            'domain_url' => $branding->domainUrl,
            'company_email' => $branding->companyEmail,
            'company_landline' => $branding->companyLandline,
            'company_mobile' => $branding->companyMobile,
            'footer_link' => $branding->footerLink,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private static function flattenMatchMakingBranding(?PDFBranding $branding): array
    {
        if ($branding === null) {
            return [];
        }

        return self::compactBody([
            'logo_url' => $branding->logoUrl,
            'domain_url' => $branding->domainUrl,
            'footer_link' => $branding->footerLink,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private static function flattenChartStyle(?PDFBranding $branding): array
    {
        if ($branding === null || $branding->chartStyle === null || $branding->chartStyle === '') {
            return [];
        }

        return ['chart_style' => strtoupper($branding->chartStyle)];
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private static function compactBody(array $body): array
    {
        return array_filter($body, static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array{first_name?: string, last_name?: string}
     */
    private static function splitFullName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, static fn ($value) => $value !== ''));

        if ($parts === []) {
            return [];
        }

        if (count($parts) === 1) {
            return ['first_name' => $parts[0]];
        }

        return [
            'first_name' => $parts[0],
            'last_name' => implode(' ', array_slice($parts, 1)),
        ];
    }
}
