<?php

declare(strict_types=1);

namespace AstrologyAPI\Errors;

/** Invalid parameters — check your request data. HTTP 400 */
class ValidationError extends AstrologyAPIException {}