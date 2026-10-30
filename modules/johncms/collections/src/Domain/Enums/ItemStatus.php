<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Enums;

use DateTimeInterface;

/**
 * Whether the site shows an item, worked out from its switch and its publishing window.
 */
enum ItemStatus: string
{
    case PUBLISHED = 'published';
    case HIDDEN = 'hidden';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';

    public static function of(bool $active, ?DateTimeInterface $from, ?DateTimeInterface $to, DateTimeInterface $now): self
    {
        return match (true) {
            ! $active                     => self::HIDDEN,
            $from !== null && $from > $now => self::SCHEDULED,
            $to !== null && $to < $now     => self::EXPIRED,
            default                        => self::PUBLISHED,
        };
    }
}
