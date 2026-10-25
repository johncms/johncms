<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Enums;

use DateTimeInterface;

/**
 * Where an article stands with the readers, worked out from whether it is switched on and the
 * dates it is shown between. An article switched off is a draft whatever its dates say; one whose
 * end has passed is expired, even if it never started.
 */
enum ArticleStatus: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';

    public static function of(bool $active, ?DateTimeInterface $from, ?DateTimeInterface $to, DateTimeInterface $now): self
    {
        return match (true) {
            ! $active                     => self::DRAFT,
            $to !== null && $to < $now    => self::EXPIRED,
            $from !== null && $from > $now => self::SCHEDULED,
            default                       => self::PUBLISHED,
        };
    }
}
