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
use Johncms\Casts\SpecialChars;
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
 * @property bool $email_confirmed
 * @property string $confirmation_code
 * @property string $new_email
 * @property string $admin_notes
 *
 * @property bool $is_online - Пользователь онлайн или нет?
 * @property string $rights_name - Название прав доступа
 * @property string $profile_url - URL страницы профиля пользователя
 * @property string $search_ip_url - URL страницы поиска по IP
 * @property string $whois_ip_url - URL страницы whois IP
 * @property string $search_ip_via_proxy_url - URL страницы поиска по IP за прокси
 * @property string $whois_ip_via_proxy_url - URL страницы whois IP за прокси
 * @property array $ban - Массив банов.
 * @property bool $is_valid - проверка валидности пользователя
 * @property bool $is_birthday - у пользователя день рождения?
 * @property string $birthday_date - дата рождения пользователя
 * @property string $display_place - местоположение
 * @property string $formatted_about - О себе в подготовленном для отображения виде
 * @property string $website - Сайт
 * @property string $last_visit - Последний визит
 * @property array $photo - Фотография пользователя
 * @property UserConfig $config - Настройки пользователя
 *
 * @method Builder approved() - Предустановленное условие для выборки подтвержденных пользователей
 * @method Builder online() - Выбрать пользователей онлайн
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
        'admin_notes'  => SpecialChars::class,

        'notification_settings' => 'array',
        'email_confirmed'       => 'bool',
    ];

    protected $fillable = [
        'id',
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
        'email_confirmed',
        'confirmation_code',
        'new_email',
        'admin_notes',
    ];

    /**
     * Выборка только подтвержденных пользователей
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeApproved(Builder $query): Builder
    {
        $query->where('preg', '=', 1);

        $config = di('config')['johncms'];
        if (! empty($config['user_email_confirmation'])) {
            $query->where('email_confirmed', '=', 1);
        }

        return $query;
    }

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

    public function isValid(): bool
    {
        return $this->is_valid;
    }
}
