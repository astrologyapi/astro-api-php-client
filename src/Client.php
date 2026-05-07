<?php

declare(strict_types=1);

namespace AstrologyAPI;

use AstrologyAPI\Http\HttpClient;
use AstrologyAPI\Namespaces\Chinese;
use AstrologyAPI\Namespaces\Horoscopes;
use AstrologyAPI\Namespaces\KP;
use AstrologyAPI\Namespaces\LalKitab;
use AstrologyAPI\Namespaces\Location;
use AstrologyAPI\Namespaces\Numerology;
use AstrologyAPI\Namespaces\Pdf;
use AstrologyAPI\Namespaces\Tarot;
use AstrologyAPI\Namespaces\Vedic;
use AstrologyAPI\Namespaces\Western;
use AstrologyAPI\Namespaces\WesternTransit;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;

/**
 * Entry point for the AstrologyAPI PHP SDK.
 *
 * All 11 namespaces are lazily instantiated on first access.
 *
 * Usage:
 *   $api = new Client('your-api-key');
 *
 *   // Vedic
 *   $details = $api->vedic()->getBirthDetails($birthData);
 *
 *   // PDF (returns binary string)
 *   $pdf = $api->pdf()->vedic->getBasicHoroscope($birthData);
 *   file_put_contents('report.pdf', $pdf);
 *
 * Environment variable usage:
 *   $api = Client::fromEnv(); // reads ASTROLOGYAPI_API_KEY
 */
class Client
{
    private HttpClient $http;

    private ?Vedic          $vedic          = null;
    private ?KP             $kp             = null;
    private ?LalKitab       $lalkitab       = null;
    private ?Western        $western        = null;
    private ?WesternTransit $westernTransit = null;
    private ?Numerology     $numerology     = null;
    private ?Horoscopes     $horoscopes     = null;
    private ?Tarot          $tarot          = null;
    private ?Chinese        $chinese        = null;
    private ?Pdf            $pdf            = null;
    private ?Location       $location       = null;

    /**
     * @param string $apiKey  Your AstrologyAPI access token key.
     * @param array{
     *     base_url?: string,
     *     pdf_base_url?: string,
     *     timeout?:  float,
     *     retries?:  int,
     *     user_id?: string,
     *     guzzle?: ClientInterface,
     *     request_observer?: callable,
     *     response_observer?: callable,
     * } $options
     */
    public function __construct(
        private string $apiKey,
        private array $options = []
    ) {
        $guzzle = $options['guzzle'] ?? new GuzzleClient();
        if (!$guzzle instanceof ClientInterface) {
            throw new \InvalidArgumentException('The "guzzle" option must implement GuzzleHttp\\ClientInterface.');
        }

        $this->http = new HttpClient($guzzle, $this->apiKey, $options);
    }

    // ── Namespace accessors ───────────────────────────────────────────────────

    public function vedic(): Vedic {
        return $this->vedic ??= new Vedic($this->http);
    }

    public function kp(): KP {
        return $this->kp ??= new KP($this->http);
    }

    public function lalKitab(): LalKitab {
        return $this->lalkitab ??= new LalKitab($this->http);
    }

    public function western(): Western {
        return $this->western ??= new Western($this->http);
    }

    public function westernTransit(): WesternTransit {
        return $this->westernTransit ??= new WesternTransit($this->http);
    }

    public function numerology(): Numerology {
        return $this->numerology ??= new Numerology($this->http);
    }

    public function horoscopes(): Horoscopes {
        return $this->horoscopes ??= new Horoscopes($this->http);
    }

    public function tarot(): Tarot {
        return $this->tarot ??= new Tarot($this->http);
    }

    public function chinese(): Chinese {
        return $this->chinese ??= new Chinese($this->http);
    }

    public function pdf(): Pdf {
        return $this->pdf ??= new Pdf($this->http);
    }

    public function location(): Location {
        return $this->location ??= new Location($this->http);
    }

    // ── Static factory ────────────────────────────────────────────────────────

    /**
     * Create a client from environment variables.
     *
     * Reads the ASTROLOGYAPI_API_KEY environment variable and, when present,
     * also forwards ASTROLOGYAPI_USER_ID for Basic auth credentials.
     *
     * @throws \RuntimeException if the environment variable is not set.
     */
    public static function fromEnv(array $options = []): self
    {
        $apiKey = getenv('ASTROLOGYAPI_API_KEY')
            ?: throw new \RuntimeException(
                'The ASTROLOGYAPI_API_KEY environment variable is not set.'
            );

        $userId = getenv('ASTROLOGYAPI_USER_ID');
        if ($userId !== false && $userId !== '') {
            $options['user_id'] ??= $userId;
        }

        return new self($apiKey, $options);
    }
}
