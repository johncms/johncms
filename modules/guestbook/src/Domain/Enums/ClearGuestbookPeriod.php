<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Domain\Enums;

enum ClearGuestbookPeriod: int
{
    case OlderThanWeek = 0;
    case OlderThanDay = 1;
    case All = 2;

    /**
     * Maximum age of the entries to keep, in seconds. Null means delete everything.
     */
    public function maxAge(): ?int
    {
        return match ($this) {
            self::OlderThanWeek => 604800,
            self::OlderThanDay  => 86400,
            self::All           => null,
        };
    }
}
