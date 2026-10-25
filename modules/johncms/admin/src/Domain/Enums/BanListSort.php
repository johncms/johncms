<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * What the ban panel is ordered by, always the most first: the ban that ends last, or the
 * account with the most violations.
 */
enum BanListSort: string
{
    case TIME = 'time';
    case VIOLATIONS = 'violations';

    /**
     * The column of the selection the list is ordered by.
     */
    public function column(): string
    {
        return match ($this) {
            self::TIME       => 'bantime',
            self::VIOLATIONS => 'bancount',
        };
    }
}
