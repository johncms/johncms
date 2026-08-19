<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Auth\AuthTables;

/**
 * A role granted to one account, beyond the default ones everybody has.
 *
 * @mixin Builder
 * @property int      $id
 * @property int      $user_id
 * @property int      $role_id
 * @property int|null $granted_by
 * @property int      $granted_at
 * @property int|null $expires_at
 */
class UserRole extends Model
{
    protected $table = AuthTables::USER_ROLES;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id',
        'granted_by',
        'granted_at',
        'expires_at',
    ];
}
