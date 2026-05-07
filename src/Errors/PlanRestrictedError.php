<?php

declare(strict_types=1);

namespace AstrologyAPI\Errors;

/**
 * Thrown when the requested endpoint is not available on the caller's plan.
 *
 * HTTP 403. To gain access, upgrade the subscription at astrologyapi.com.
 */
class PlanRestrictedError extends AstrologyAPIException
{
}
