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

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Role
 *
 * @package Johncms\Users
 * @mixin Builder
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $user_id
 * @property string $token
 * @property string $ip
 * @property string $user_agent
 *
 * @property User $user
 */
class StoredAuth extends Model
{
    protected $table = 'stored_auth';

    protected $fillable = [
        'user_id',
        'token',
        'ip',
        'user_agent',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
