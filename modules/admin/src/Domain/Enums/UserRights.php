<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * Уровни прав пользователей, значимые для админпанели.
 */
enum UserRights: int
{
    case MODERATOR = 6;
    case ADMIN = 7;
    case SUPER_ADMIN = 9;
}
