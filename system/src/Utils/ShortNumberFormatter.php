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
 * Formats large numbers in a shortened form: 1500 -> 1.5K.
 */
final class ShortNumberFormatter
{
    private const PREFIXES = 'KMGTPEZY';

    public static function format(int|float $number): string
    {
        if ($number < 1000) {
            return (string) $number;
        }

        $prefixIndex = -1;
        while ($number >= 1000) {
            $number /= 1000;
            ++$prefixIndex;
        }

        if ($number > 100) {
            $number = floor($number);
        }

        return round($number, 2) . self::PREFIXES[$prefixIndex];
    }
}
