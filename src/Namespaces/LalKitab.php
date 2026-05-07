<?php

declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Models\BirthData;

class LalKitab
{
    public function __construct(private HttpClient $http)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function getHoroscope(BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post(
            'lalkitab_horoscope',
            array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getDebts(BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post(
            'lalkitab_debts',
            array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getRemedies(string $planetName, BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post(
            'lalkitab_remedies/' . $planetName,
            array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getHouses(BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post(
            'lalkitab_houses',
            array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]),
            ['encoding' => 'form-urlencoded']
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function getPlanets(BirthData $data, string $ayanamsha = 'LAHIRI'): array
    {
        return $this->http->post(
            'lalkitab_planets',
            array_merge($data->toArray(), ['ayanamsha' => $ayanamsha]),
            ['encoding' => 'form-urlencoded']
        );
    }
}
