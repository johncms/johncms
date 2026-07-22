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

/**
 * Formats a duration either as a number of days or as an H:i:s clock value.
 */
final class DurationFormatter
{
    public static function format(int $seconds): string
    {
        $seconds = max(0, $seconds);

        if ($seconds >= 86400) {
            $days = intdiv($seconds, 86400);

            return $days . ' ' . dn__('system', 'Day', 'Days', $days);
        }

        return date('G:i:s', mktime(0, 0, $seconds));
    }
}
