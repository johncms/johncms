<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Utils;

interface DateFormatterInterface
{
    /**
     * Formats a Unix timestamp for display, applying the site and user time shift.
     */
    public function format(int $timestamp): string;
}
