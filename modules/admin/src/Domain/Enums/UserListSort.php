<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * Доступные режимы сортировки списка пользователей.
 */
enum UserListSort: string
{
    case ID = 'id';
    case NICK = 'nick';
    case IP = 'ip';

    /**
     * Колонка БД, по которой выполняется сортировка (только белый список).
     */
    public function column(): string
    {
        return match ($this) {
            self::ID   => 'id',
            self::NICK => 'name',
            self::IP   => 'ip',
        };
    }
}
