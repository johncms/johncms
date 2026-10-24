<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * How recently the account was registered.
 */
enum UserListRegistered: string
{
    case ANY = 'any';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    /**
     * The account was registered after this moment; null puts no bound.
     */
    public function registeredAfter(int $now): ?int
    {
        return match ($this) {
            self::DAY   => $now - 86400,
            self::WEEK  => $now - 7 * 86400,
            self::MONTH => $now - 30 * 86400,
            self::YEAR  => $now - 365 * 86400,
            self::ANY   => null,
        };
    }
}
