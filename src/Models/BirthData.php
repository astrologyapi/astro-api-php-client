<?php

declare(strict_types=1);

namespace AstrologyAPI\Models;

/**
 * Common birth data required by most astrology endpoints.
 */
final readonly class BirthData
{
    public function __construct(
        public int $day,
        public int $month,
        public int $year,
        public int $hour,
        public int $min,
        public float $lat,
        public float $lon,
        public float $tzone,
    ) {
    }

    /**
     * Convert to key-value pairs suitable for form POST.
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'day'   => $this->day,
            'month' => $this->month,
            'year'  => $this->year,
            'hour'  => $this->hour,
            'min'   => $this->min,
            'lat'   => $this->lat,
            'lon'   => $this->lon,
            'tzone' => $this->tzone,
        ];
    }

    /**
     * Create from an associative array (e.g. from JSON or request).
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            day:   (int) ($data['day'] ?? 0),
            month: (int) ($data['month'] ?? 0),
            year:  (int) ($data['year'] ?? 0),
            hour:  (int) ($data['hour'] ?? 0),
            min:   (int) ($data['min'] ?? 0),
            lat:   (float) ($data['lat'] ?? 0.0),
            lon:   (float) ($data['lon'] ?? 0.0),
            tzone: (float) ($data['tzone'] ?? 0.0),
        );
    }
}
