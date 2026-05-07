<?php

declare(strict_types=1);

namespace AstrologyAPI\Errors;

/**
 * Thrown when credentials are invalid or missing.
 * HTTP 401 / 403
 */
class AuthenticationError extends AstrologyAPIException {}