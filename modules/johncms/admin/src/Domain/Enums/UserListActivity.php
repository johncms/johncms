<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * How recently the account was last seen.
 */
enum UserListActivity: string
{
    case ANY = 'any';
    case ONLINE = 'online';
    case DAY = 'day';
    case WEEK = 'week';
    case INACTIVE = 'inactive';

    /**
     * The account was seen after this moment; null puts no lower bound. Online is the window the
     * rest of the site counts a visitor online for.
     */
    public function seenAfter(int $now): ?int
    {
        return match ($this) {
            self::ONLINE => $now - 300,
            self::DAY    => $now - 86400,
            self::WEEK   => $now - 7 * 86400,
            default      => null,
        };
    }

    /**
     * The account was not seen since this moment: half a year without a visit.
     */
    public function seenBefore(int $now): ?int
    {
        return $this === self::INACTIVE ? $now - 182 * 86400 : null;
    }
}
