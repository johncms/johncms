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
 * One permission granted to one role.
 *
 * @mixin Builder
 * @property int    $id
 * @property int    $role_id
 * @property string $permission
 */
class RolePermission extends Model
{
    protected $table = AuthTables::ROLE_PERMISSIONS;

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission',
    ];
}
