<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * How far back the sign-in log looks.
 */
enum AuthLogPeriod: string
{
    case ANY = 'any';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';

    /**
     * The entry was written after this moment; null puts no bound.
     */
    public function after(int $now): ?int
    {
        return match ($this) {
            self::DAY   => $now - 86400,
            self::WEEK  => $now - 7 * 86400,
            self::MONTH => $now - 30 * 86400,
            self::ANY   => null,
        };
    }
}
