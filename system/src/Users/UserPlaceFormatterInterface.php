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

use Twig\Markup;

interface UserPlaceFormatterInterface
{
    /**
     * Returns a human readable link for the page a user is currently on.
     *
     * It is a link by contract, so it is markup: a template prints it as it is, and under
     * autoescape it would otherwise show its own tags.
     */
    public function format(?string $place): Markup;
}
