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
 * Class User
 *
 * @mixin Builder
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property int $karma_user
 * @property int $points
 * @property int $type
 * @property int $time
 * @property string $text
 *
 */
class Karma extends Model
{
    protected $table = 'karma_users';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'karma_user',
        'points',
        'type',
        'time',
        'text',
    ];
}
