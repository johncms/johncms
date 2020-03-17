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
use Johncms\Casts\Ip;
use Johncms\Casts\Serialize;
use Johncms\Casts\UserSettings;
use Johncms\System\Users\UserConfig;

/**
 * Class User
 *
 * @mixin Builder
 * @property int $id
 * @property string $name
 * @property string $name_lat
 * @property string $password
 * @property int $rights
 * @property int $failed_login
 * @property string $imname
 * @property string $sex
 * @property int $komm
 * @property int $postforum
 * @property int $postguest
 * @property int $yearofbirth
 * @property int $datereg
 * @property int $lastdate
 * @property string $mail
 * @property int $icq
 * @property string $skype
 * @property string $jabber
 * @property string $www
 * @property string $about
 * @property string $live
 * @property string $mibile
 * @property string $status
 * @property string $ip
 * @property string $ip_via_proxy
 * @property string $browser
 * @property bool $preg
 * @property string $regadm
 * @property bool $mailvis
 * @property int $dayb
 * @property int $monthb
 * @property int $sestime
 * @property int $total_on_site
 * @property int $lastpost
 * @property string $rest_code
 * @property int $rest_time
 * @property int $movings
 * @property string $place
 * @property UserConfig $set_user
 * @property array $set_forum
 * @property array $set_mail
 * @property int $karma_plus
 * @property int $karma_minus
 * @property int $karma_time
 * @property bool $karma_off
 * @property int $comm_count
 * @property int $comm_old
 * @property array $smileys
 * @property array $notification_settings
 *
 * @property bool $is_online - Пользователь онлайн или нет?
 * @property string $rights_name - Название прав доступа
 * @property string $profile_url - URL страницы профиля пользователя
 * @property string $search_ip_url - URL страницы поиска по IP
 * @property string $search_ip_via_proxy_url - URL страницы поиска по IP за прокси
 * @property array $ban - Массив банов.
 *
 * @method Builder approved() - Предустановленное условие для выборки подтвержденных пользователей
 */
class User extends Model
{
    use UserMutators;
    use UserRelations;

    public $timestamps = false;

    protected $casts = [
        'preg'      => 'bool',
        'mailvis'   => 'bool',
        'karma_off' => 'bool',

        'set_user'     => UserSettings::class,
        'set_forum'    => Serialize::class,
        'set_mail'     => Serialize::class,
        'smileys'      => Serialize::class,
        'ip'           => Ip::class,
        'ip_via_proxy' => Ip::class,

        'notification_settings' => 'array',
    ];

    protected $fillable = [
        'name',
        'name_lat',
        'password',
        'rights',
        'failed_login',
        'imname',
        'sex',
        'komm',
        'postforum',
        'postguest',
        'yearofbirth',
        'datereg',
        'lastdate',
        'mail',
        'icq',
        'skype',
        'jabber',
        'www',
        'about',
        'live',
        'mibile',
        'status',
        'ip',
        'ip_via_proxy',
        'browser',
        'preg',
        'regadm',
        'mailvis',
        'dayb',
        'monthb',
        'sestime',
        'total_on_site',
        'lastpost',
        'rest_code',
        'rest_time',
        'movings',
        'place',
        'set_user',
        'set_forum',
        'set_mail',
        'karma_plus',
        'karma_minus',
        'karma_time',
        'karma_off',
        'comm_count',
        'comm_old',
        'smileys',
        'notification_settings',
    ];

    /**
     * Выборка только подтвержденных пользователей
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('preg', '=', 1);
    }
}
