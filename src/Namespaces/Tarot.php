<?php
declare(strict_types=1);

namespace AstrologyAPI\Namespaces;

use AstrologyAPI\Http\HttpClient;

/**
 * Tarot card reading namespace.
 */
class Tarot
{
    public function __construct(private HttpClient $http) {}

    /** @return array<string,mixed> */
    public function getDailyCard(): array { return $this->http->post('tarot_predictions', []); }

    /** @return array<string,mixed> */
    public function getDetailedReading(int $tarotId = 5): array
    {
        return $this->http->post('yes_no_tarot', ['tarot_id' => $tarotId]);
    }
}
