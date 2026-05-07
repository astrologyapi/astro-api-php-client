<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

/**
 * Chinese Astrology namespace.
 */
class Chinese
{
    public function __construct(private HttpClient $http) {}

    /**
     * Get Chinese horoscope for a person.
     *
     * The live API exposes this as `chinese_zodiac`; this method keeps the
     * legacy PHP name while targeting the current endpoint from the shared
     * Postman/Node reference.
     *
     * @return array<string,mixed>
     */
    public function getChineseHoroscope(BirthData $data): array
    {
        return $this->http->post('chinese_zodiac', $data->toArray());
    }

}
