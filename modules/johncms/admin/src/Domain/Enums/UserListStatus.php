<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * The accounts the list of users shows: all of them, the ones waiting for an administrator to
 * let them in, the ones that have not confirmed their e-mail, or the ones under a ban that has not
 * run out.
 */
enum UserListStatus: string
{
    case ALL = 'all';
    case PENDING = 'pending';
    case UNCONFIRMED = 'unconfirmed';
    case BANNED = 'banned';
}
