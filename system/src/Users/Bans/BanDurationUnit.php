<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\Bans;

/**
 * What the length of a ban is counted in, as the ban forms send it, and how far each goes: an hour
 * of minutes, a day of hours, a month of days. "Until lifted" is ten years.
 */
enum BanDurationUnit: int
{
    case MINUTES = 1;
    case HOURS = 2;
    case DAYS = 3;
    case UNTIL_LIFTED = 4;

    /**
     * The length of a ban of this many units, in seconds.
     */
    public function seconds(int $amount): int
    {
        return match ($this) {
            self::MINUTES      => min($amount, 60) * 60,
            self::HOURS        => min($amount, 24) * 3600,
            self::DAYS         => min($amount, 30) * 86400,
            self::UNTIL_LIFTED => 315360000,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::MINUTES      => d__('system', 'Minutes (60 max.)'),
            self::HOURS        => d__('system', 'Hours (24 max.)'),
            self::DAYS         => d__('system', 'Days (30 max.)'),
            self::UNTIL_LIFTED => d__('system', 'Till cancel'),
        };
    }
}
