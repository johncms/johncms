<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Exceptions;

use InvalidArgumentException;
use RuntimeException;

/**
 * Signals that the request must be answered with an HTTP redirect.
 *
 * Thrown by the redirect() helper instead of sending a Location header and calling exit,
 * so that the HTTP layer stays the only place that writes to the output.
 */
final class HttpRedirectException extends RuntimeException
{
    public function __construct(
        private readonly string $url,
        private readonly int $status = 302
    ) {
        // Validated here so that a wrong status fails at the redirect() call site, with that call
        // in the stack trace, instead of surfacing later as an InvalidArgumentException from
        // RedirectResponse in the HTTP layer.
        if ($url === '') {
            throw new InvalidArgumentException('The redirect URL must not be empty.');
        }

        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException(sprintf('The HTTP status %d is not a redirect status.', $status));
        }

        parent::__construct(sprintf('Redirect with status %d', $status));
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
