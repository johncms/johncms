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
 * @property int $ban_time
 * @property int $ban_while
 * @property int $ban_type
 * @property string $ban_who
 * @property int $ban_ref
 * @property string $ban_reason
 * @property string $ban_raz
 *
 * @property bool $is_active
 *
 * @method Builder active() - Предустановленное условие для выборки только активных банов
 */
class Ban extends Model
{
    protected $table = 'cms_ban_users';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ban_time',
        'ban_while',
        'ban_type',
        'ban_who',
        'ban_ref',
        'ban_reason',
        'ban_raz',
    ];

    /**
     * Выборка только активных банов
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('ban_time', '>', time());
    }

    /**
     * Активность бана
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->ban_time > time();
    }
}
