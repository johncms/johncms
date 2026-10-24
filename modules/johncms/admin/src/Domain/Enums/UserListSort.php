<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * What the list of users can be ordered by. The query maps every case to its column, so a
 * value from the address never reaches the SQL itself.
 */
enum UserListSort: string
{
    case ID = 'id';
    case NICK = 'nick';
    case IP = 'ip';
    case LAST_VISIT = 'visit';
    case REGISTERED = 'registered';
    case KARMA = 'karma';

    /**
     * The direction the list takes when the column is first clicked: the newest, the most recent
     * and the highest first, names and addresses from A.
     */
    public function defaultDirection(): SortDirection
    {
        return match ($this) {
            self::NICK, self::IP => SortDirection::ASC,
            default              => SortDirection::DESC,
        };
    }
}
