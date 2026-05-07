<?php

declare(strict_types=1);

namespace AstrologyAPI\Models;

/**
 * Match-making data pairing male and female birth details.
 *
 * Used internally by the Vedic matchmaking endpoints.
 * Male fields are prefixed m_, female fields are prefixed f_.
 */
final readonly class MatchData
{
    public function __construct(
        public BirthData $male,
        public BirthData $female,
    ) {
    }

    /**
     * Convert to a flat key-value array suitable for API POST.
     * Male fields get an m_ prefix; female fields get an f_ prefix.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $params = [];

        foreach ($this->male->toArray() as $key => $value) {
            $params['m_' . $key] = $value;
        }
        foreach ($this->female->toArray() as $key => $value) {
            $params['f_' . $key] = $value;
        }

        return $params;
    }
}
