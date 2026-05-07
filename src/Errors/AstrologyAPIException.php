<?php

declare(strict_types=1);

namespace AstrologyAPI\Errors;

/**
 * Base exception for all AstrologyAPI errors.
 */
class AstrologyAPIException extends \Exception
{
    /**
     * @var int The HTTP status code.
     */
    protected int $httpCode;

    /**
     * @param string      $message  Human-readable error description.
     * @param int         $code     Internal error code (usually HTTP status).
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->httpCode = $code;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Return the HTTP status code for this error.
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }
}
