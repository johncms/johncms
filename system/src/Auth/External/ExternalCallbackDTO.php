<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

/**
 * The request the provider sent the visitor back with, as a plain object.
 *
 * A provider never sees the Request itself: HTTP types belong to the HTTP layer, and a provider
 * is an adapter to somebody else's API. Assembling this is the controller's job.
 */
final readonly class ExternalCallbackDTO
{
    /**
     * @param array<string, string> $query Query parameters of the callback.
     * @param array<string, string> $body  Body parameters, for the providers that post back.
     */
    public function __construct(
        public array $query = [],
        public array $body = [],
    ) {
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->query[$key] ?? $this->body[$key] ?? $default;
    }
}
