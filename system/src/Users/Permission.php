<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $description
 */
class Permission extends Model
{
    protected $table = 'permissions';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module_name',
    ];
}
