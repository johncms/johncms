<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * Сортировка списка банов.
 */
enum BanListSort: string
{
    case TIME = 'time';
    case VIOLATIONS = 'violations';

    /**
     * Колонка/алиас выборки для ORDER BY.
     */
    public function column(): string
    {
        return match ($this) {
            self::TIME       => 'bantime',
            self::VIOLATIONS => 'bancount',
        };
    }
}
