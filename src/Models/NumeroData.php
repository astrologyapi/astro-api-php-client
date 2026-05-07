<?php

declare(strict_types=1);

namespace AstrologyAPI\Models;

/**
 * Data for numerology endpoints.
 */
final readonly class NumeroData
{
    public function __construct(
        public int $day,
        public int $month,
        public int $year,
        public string $name = '',
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $arr = [
            'day'   => $this->day,
            'month' => $this->month,
            'year'  => $this->year,
        ];
        if ($this->name !== '') {
            $arr['name'] = $this->name;
        }
        return $arr;
    }
}
