<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Api;

/**
 * @property int                 $id           Идентификатор пользователя
 * @property string              $name         Никнейм пользователя
 * @property string              $name_lat     (УСТАРЕЛО) Транслитерированный никнейм
 * @property string              $password     Пароль пользователя
 * @property int                 $rights
 * @property int                 $failed_login Число неудачных попытк залогиниться
 * @property string              $imname       Имя, фамилия пользователя
 * @property string              $sex          Пол
 * @property int                 $komm         Число комментариев
 * @property int                 $postforum    Число постов на форуме
 * @property int                 $postguest    Число постов в мини-чате
 * @property int                 $datereg      Дата/время регистрации на сайте
 * @property int                 $lastdate     Дата/время последнего визита
 * @property string              $mail         Email пользователя (используется и для восстановления пароля)
 * @property                     $icq          Идентификатор в ICS
 * @property string              $skype        Идентификатор в Skype
 * @property string              $jabber       Идентификатор в Jabber
 * @property string              $www          URL сайтов пользователля
 * @property string              $about        Свободный по содержанию текст "О себе"
 * @property string              $live         Место проживания (город и т.д.)
 * @property string              $mibile       Телефон
 * @property string              $status       Статус на сайте (выводится как подпись под ником)
 * @property                     $ip           Реальный IP адрес клиента
 * @property                     $ip_via_proxy IP адрес, вычисляемый по заголовкам Proxy серверов
 * @property string              $browser      User Agent браузера пользователя
 * @property                     $preg         Подтверждена ли регистрация на сайте
 * @property string              $regadm       Кто подтвердил регистрацию (если была включена модерация регистрации)
 * @property                     $mailvis      Показывать ли Email пользователя в анкете?
 * @property                     $yearofbirth  Год рождения
 * @property                     $dayb         День рожденья
 * @property                     $monthb       Месяц рожденья
 * @property                     $sestime
 * @property                     $total_on_site
 * @property                     $lastpost
 * @property                     $rest_code
 * @property                     $rest_time
 * @property                     $movings
 * @property                     $place
 * @property                     $set_user
 * @property                     $set_forum
 * @property                     $set_mail
 * @property                     $karma_plus
 * @property                     $karma_minus
 * @property                     $karma_time
 * @property                     $karma_off
 * @property                     $comm_count
 * @property                     $comm_old
 * @property                     $smileys
 * @property                     $ban
 * @property UserConfigInterface $config
 */
interface UserInterface
{
    /**
     * User validation
     */
    public function isValid() : bool;
}
