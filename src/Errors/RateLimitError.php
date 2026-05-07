<?php

declare(strict_types=1);

namespace AstrologyAPI\Errors;

/** Rate limit exceeded — check retry-after header if available. HTTP 429 */
class RateLimitError extends AstrologyAPIException {}