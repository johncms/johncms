<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

interface UserPlaceFormatterInterface
{
    /**
     * Returns a human readable link for the page a user is currently on.
     */
    public function format(?string $place): string;
}
