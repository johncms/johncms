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

use RuntimeException;

/**
 * The route matches the URI, but not the request method.
 *
 * Follows the same shape as HttpRedirectException and PageNotFoundException: the HTTP layer
 * catches it and builds the response, so the front controller does not compose responses itself.
 */
final class MethodNotAllowedException extends RuntimeException
{
    /**
     * @param array<int, string> $allowedMethods Methods the route does accept
     */
    public function __construct(private readonly array $allowedMethods = [])
    {
        parent::__construct(sprintf('Allowed methods: %s', implode(', ', $allowedMethods)));
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
