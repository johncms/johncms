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
 * Class GuestSession
 *
 * @mixin Builder
 * @property int $session_id
 * @property string $ip
 * @property string $ip_via_proxy
 * @property string $browser
 * @property int $lastdate
 * @property int $sestime
 * @property int $views
 * @property int $movings
 * @property string $place
 *
 * @property bool $is_online - Пользователь онлайн или нет?
 * @property string $search_ip_url - URL страницы поиска по IP
 * @property string $search_ip_via_proxy_url - URL страницы поиска по IP за прокси
 *
 * @method Builder online() - Выбрать пользователей онлайн
 */
class GuestSession extends Model
{
    use GuestSessionMutators;

    protected $table = 'cms_sessions';

    protected $fillable = [
        'session_id',
        'ip',
        'ip_via_proxy',
        'browser',
        'lastdate',
        'sestime',
        'views',
        'movings',
        'place',
    ];

    /**
     * Выборка только пользователей онлайн
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('lastdate', '>', (time() - 300));
    }
}
