<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Domain\Enums;

/**
 * Vote direction for an album photo.
 */
enum VoteType: string
{
    case Plus = 'plus';
    case Minus = 'minus';

    /**
     * The stored vote value in cms_album_votes.
     */
    public function storedValue(): int
    {
        return match ($this) {
            self::Plus  => 1,
            self::Minus => -1,
        };
    }
}
