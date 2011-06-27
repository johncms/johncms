<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Restricted access');

class counters
{
    /*
    -----------------------------------------------------------------
    Счетчик Фотоальбомов / фотографий юзеров
    -----------------------------------------------------------------
    */
    static function album()
    {
        $albumcount = mysql_result(mysql_query("SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`"), 0);
        $photocount = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files`"), 0);
        $newcount = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` > '1'"), 0);
        return $albumcount . '&#160;/&#160;' . $photocount . ($newcount ? '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/users/album.php?act=top">+' . $newcount . '</a></span>' : '');
    }

    /*
    -----------------------------------------------------------------
    Статистика загрузок
    -----------------------------------------------------------------
    */
    static function downloads()
    {
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file'"), 0);
        $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'file'"), 0);
        if ($new > 0)
            $total .= '&#160;/&#160;<span class="red"><a href="/download/?act=new">+' . $new . '</a></span>';
        return $total;
    }

    /*
    -----------------------------------------------------------------
    Статистика Форума
    -----------------------------------------------------------------
    */
    static function forum()
    {
        $total_thm = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't'" . (core::$user_rights >= 7 ? "" : " AND `close` != '1'")), 0);
        $total_msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm'" . (core::$user_rights >= 7 ? "" : " AND `close` != '1'")), 0);
        $out = $total_thm . '&#160;/&#160;' . $total_msg . '';
        if (core::$user_id) {
            $new = self::forum_new();
            if ($new)
                $out .= '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/forum/index.php?act=new">+' . $new . '</a></span>';
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Счетчик непрочитанных тем на форуме
    -----------------------------------------------------------------
    $mod = 0   Возвращает число непрочитанных тем
    $mod = 1   Выводит ссылки на непрочитанное
    -----------------------------------------------------------------
    */
    static function forum_new($mod = 0)
    {
        if (core::$user_id) {
            $req = mysql_query("SELECT COUNT(*) FROM `forum`
                LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . core::$user_id . "'
                WHERE `forum`.`type`='t'" . (core::$user_rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "
                AND (`cms_forum_rdm`.`topic_id` Is Null
                OR `forum`.`time` > `cms_forum_rdm`.`time`)");
            $total = mysql_result($req, 0);
            if ($mod)
                return '<a href="index.php?act=new">' . core::$lng['unread'] . '</a>&#160;' . ($total ? '<span class="red">(<b>' . $total . '</b>)</span>' : '');
            else
                return $total;
        } else {
            if ($mod)
                return '<a href="index.php?act=new">' . core::$lng['last_activity'] . '</a>';
            else
                return false;
        }
    }

    /*
    -----------------------------------------------------------------
    Статистика галлереи
    -----------------------------------------------------------------
    $mod = 1    будет выдавать только колличество новых картинок
    -----------------------------------------------------------------
    */
    static function gallery($mod = 0)
    {
        $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'ft'"), 0);
        if ($mod == 0) {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `gallery` WHERE `type` = 'ft'"), 0);
            $out = $total;
            if ($new > 0)
                $out .= '&#160;/&#160;<span class="red"><a href="/gallery/index.php?act=new">+' . $new . '</a></span>';
        } else {
            $out = $new;
        }
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Статистика гостевой
    -----------------------------------------------------------------
    $mod = 1    колличество новых в гостевой
    $mod = 2    колличество новых в Админ-Клубе
    -----------------------------------------------------------------
    */
    static function guestbook($mod = 0)
    {
        $count = 0;
        switch ($mod) {
            case 1:
                $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='0' AND `time` > '" . (time() - 86400) . "'"), 0);
                break;

            case 2:
                if (core::$user_rights >= 1)
                    $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time` > '" . (time() - 86400) . "'"), 0);
                break;

            default:
                $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='0' AND `time` > '" . (time() - 86400) . "'"), 0);
                if (core::$user_rights >= 1) {
                    $req = mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time`>'" . (time() - 86400) . "'");
                    $count = $count . '&#160;/&#160;<span class="red"><a href="guestbook/index.php?act=ga&amp;do=set">' . mysql_result($req, 0) . '</a></span>';
                }
        }
        return $count;
    }

    /*
    -----------------------------------------------------------------
    Статистика библиотеки
    -----------------------------------------------------------------
    */
    static function library()
    {
        $countf = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '1'"), 0);
        $countf1 = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'bk' AND `moder` = '1'"), 0);
        $out = $countf;
        if ($countf1 > 0)
            $out = $out . '&#160;/&#160;<span class="red"><a href="/library/index.php?act=new">+' . $countf1 . '</a></span>';
        $countm = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'"), 0);

        if ((core::$user_rights == 5 || core::$user_rights >= 6) && $countm > 0)
            $out = $out . "/<a href='" . core::$system_set['homeurl'] . "/library/index.php?act=moder'><font color='#FF0000'> M:$countm</font></a>";
        return $out;
    }

    /*
    -----------------------------------------------------------------
    Счетчик посетителей онлайн
    -----------------------------------------------------------------
    */
    static function online()
    {
        $users = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `lastdate` > '" . (time() - 300) . "'"), 0);
        $guests = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > '" . (time() - 300) . "'"), 0);
        return (core::$user_id || core::$system_set['active'] ? '<a href="' . core::$system_set['homeurl'] . '/users/index.php?act=online">' . core::$lng['online'] . ': ' . $users . ' / ' . $guests . '</a>' : core::$lng['online'] . ': ' . $users . ' / ' . $guests);
    }

    /*
    -----------------------------------------------------------------
    Колличество зарегистрированных пользователей
    -----------------------------------------------------------------
    */
    static function users()
    {
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users`"), 0);
        $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `datereg` > '" . (time() - 86400) . "'"), 0);
        if ($res > 0)
            $total .= '&#160;/&#160;<span class="red">+' . $res . '</span>';
        return $total;
    }
}

?>