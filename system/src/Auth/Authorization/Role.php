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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Johncms\Auth\AuthTables;

/**
 * A named set of permissions.
 *
 * @mixin Builder
 * @property int         $id
 * @property string      $slug
 * @property string      $name
 * @property int         $level
 * @property bool        $is_system
 * @property bool        $is_default
 * @property bool        $is_guest
 * @property int         $created_at
 * @property int         $updated_at
 *
 * @property string $display_name
 */
class Role extends Model
{
    protected $table = AuthTables::ROLES;

    public $timestamps = false;

    protected $casts = [
        'is_system'  => 'bool',
        'is_default' => 'bool',
        'is_guest'   => 'bool',
    ];

    protected $fillable = [
        'slug',
        'name',
        'level',
        'is_system',
        'is_default',
        'is_guest',
        'created_at',
        'updated_at',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }

    /**
     * The name to print. A built-in role is named in the visitor's language; one the site added
     * keeps the wording it was given.
     */
    public function getDisplayNameAttribute(): string
    {
        return SystemRole::tryFrom($this->slug)?->label() ?? $this->name;
    }
}
