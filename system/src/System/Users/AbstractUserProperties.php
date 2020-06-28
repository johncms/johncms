<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Users;

abstract class AbstractUserProperties
{
    /** @var int Идентификатор пользователя */
    public $id = 0;

    /** @var string Никнейм пользователя */
    public $name = '';

    /** @var string Пароль пользователя */
    public $password = '';

    /** @var int Права доступа */
    public $rights = 0;

    /** @var int Число неудачных попытк залогиниться */
    public $failed_login = 0;

    /** @var string Имя, фамилия пользователя */
    public $imname = '';

    /** @var string Пол */
    public $sex = 'm';

    /** @var int Число комментариев */
    public $komm = 0;

    /** @var int Число постов на форуме */
    public $postforum = 0;

    /** @var int Число постов в мини-чате */
    public $postguest = 0;

    /** @var int Дата/время регистрации на сайте */
    public $datereg = 0;

    /** @var int Дата/время последнего визита */
    public $lastdate = 0;

    /** @var string Email пользователя */
    public $mail = '';

    /** @var string Идентификатор в Skype */
    public $skype = '';

    /** @var string URL сайтов пользователля */
    public $www = '';

    /** @var string О себе */
    public $about = '';

    /** @var string Место проживания */
    public $live = '';

    /** @var string Телефон */
    public $mibile = '';

    /** @var string Статус на сайте (выводится как подпись под ником) */
    public $status = '';

    /** @var string Реальный IP адрес клиента */
    public $ip = '';

    /** @var string IP адрес, вычисляемый по заголовкам Proxy серверов */
    public $ip_via_proxy = '';

    /** @var string User Agent браузера пользователя */
    public $browser = '';

    /** @var int Подтверждена ли регистрация на сайте */
    public $preg = 0;

    /** @var int Показывать ли Email пользователя в анкете? */
    public $mailvis = 0;

    /** @var int */
    public $sestime = 0;

    /** @var int Метка времени последней активности */
    public $lastpost = 0;

    /** @var string */
    public $rest_code = '';

    /** @var int */
    public $rest_time = 0;

    /** @var int */
    public $movings = 0;

    /** @var string */
    public $place = '';

    /** @var string */
    public $set_user = '';

    /** @var string */
    public $set_forum = '';

    /** @var string */
    public $set_mail = '';

    /** @var string */
    public $karma_plus = '';

    /** @var string */
    public $karma_minus = '';

    /** @var string */
    public $karma_time = '';

    /** @var int */
    public $karma_off = 0;

    /** @var int */
    public $comm_count = 0;

    /** @var int */
    public $comm_old = 0;

    /** @var string */
    public $smileys = '';

    /** @var array */
    public $ban = [];

    /** @var UserConfig */
    public $config;

    /** @var string */
    public $notification_settings;

    /** @var int */
    public $email_confirmed = 0;

    ////////////////////////////////////////////////////////////
    // Устаревшее, по возможности выпилить                    //
    ////////////////////////////////////////////////////////////

    /**
     * @deprecated
     * @var string
     */
    public $name_lat = '';

    /**
     * @deprecated
     * @var string
     */
    public $icq = '';

    /**
     * @deprecated
     * @var string
     */
    public $jabber = '';

    /**
     * Кто подтвердил регистрацию (если была включена модерация регистрации)
     *
     * @deprecated
     * @var string
     */
    public $regadm = '';

    /**
     * Год рождения
     *
     * @deprecated
     * @var int
     */
    public $yearofbirth = 0;

    /**
     * Месяц рожденья
     *
     * @deprecated
     * @var int
     */
    public $monthb = 0;

    /**
     * День рожденья
     *
     * @deprecated
     * @var int
     */
    public $dayb = 0;

    /**
     * @deprecated
     * @var int
     */
    public $total_on_site = 0;
}
