<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

/**
 * Location and timezone utilities namespace.
 */
class Location
{
    public function __construct(private HttpClient $http) {}

    /**
     * Get timezone and DST information for a geographic coordinate.
     *
     * @return array<string,mixed>
     */
    public function getTimezone(BirthData|array $data): array
    {
        $payload = $data instanceof BirthData ? $data->toArray() : $data;

        return $this->http->post('timezone_with_dst', [
            'day' => (int) ($payload['day'] ?? 0),
            'month' => (int) ($payload['month'] ?? 0),
            'year' => (int) ($payload['year'] ?? 0),
            'hour' => (int) ($payload['hour'] ?? 0),
            'min' => (int) ($payload['min'] ?? 0),
            'lat' => (float) ($payload['lat'] ?? 0.0),
            'lon' => (float) ($payload['lon'] ?? 0.0),
        ]);
    }

    /**
     * Get geo details (coordinates, timezone) for a place name.
     *
     * @param string $placeName Name of the city or place to look up.
     * @param int    $maxRows   Maximum number of results to return (default: 6).
     * @return array<string,mixed>
     */
    public function getGeoDetails(string $placeName, int $maxRows = 6): array
    {
        return $this->http->post('geo_details', [
            'place' => $placeName,
            'maxRows' => $maxRows,
        ]);
    }
}
