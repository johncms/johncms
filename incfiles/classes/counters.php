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
        global $rootpath;
        $file = $rootpath . 'files/cache/count_album.dat';
        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $album = $res['album'];
            $photo = $res['photo'];
            $new = $res['new'];
        } else {
            $album = mysql_result(mysql_query("SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`"), 0);
            $photo = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files`"), 0);
            $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > '" . (time() - 259200) . "' AND `access` > '1'"), 0);
            file_put_contents($file, serialize(array('album' => $album, 'photo' => $photo, 'new' => $new)));
        }
        return $album . '&#160;/&#160;' .$photo . ($new ? '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/users/album.php?act=top">+' . $new . '</a></span>' : '');
    }

    /*
    -----------------------------------------------------------------
    Статистика загрузок
    -----------------------------------------------------------------
    */
    static function downloads()
    {
        global $rootpath;
        $file = $rootpath . 'files/cache/count_downloads.dat';
        if (file_exists($file) && filemtime($file) > (time() - 900)) {
            $res = unserialize(file_get_contents($file));
            $total = $res['total'];
            $new = $res['new'];
        } else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file'"), 0);
            $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'file'"), 0);
            file_put_contents($file, serialize(array('total' => $total, 'new' => $new)));
        }
        if ($new) $total .= '&#160;/&#160;<span class="red"><a href="/download/?act=new">+' . $new . '</a></span>';
        return $total;
    }

    /*
    -----------------------------------------------------------------
    Статистика Форума
    -----------------------------------------------------------------
    */
    static function forum()
    {
        global $rootpath;
        $file = $rootpath . 'files/cache/count_forum.dat';
        $new = '';
        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $top = $res['top'];
            $msg = $res['msg'];
        } else {
            $top = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` != '1'"), 0);
            $msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` != '1'"), 0);
            file_put_contents($file, serialize(array('top' => $top, 'msg' => $msg)));
        }
        if (core::$user_id && ($new_msg = self::forum_new()) > 0) {
            $new = '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/forum/index.php?act=new">+' . $new_msg . '</a></span>';
        }
        return $top . '&#160;/&#160;' . $msg . $new;
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
        global $rootpath;
        $file = $rootpath . 'files/cache/count_library.dat';
        if (file_exists($file) && filemtime($file) > (time() - 3200)) {
            $res = unserialize(file_get_contents($file));
            $total = $res['total'];
            $new = $res['new'];
            $mod = $res['mod'];
        } else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '1'"), 0);
            $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'bk' AND `moder` = '1'"), 0);
            $mod = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'"), 0);
            file_put_contents($file, serialize(array('total' => $total, 'new' => $new, 'mod' => $mod)));
        }
        if ($new) $total .= '&#160;/&#160;<span class="red"><a href="/library/index.php?act=new">+' . $new . '</a></span>';
        if ((core::$user_rights == 5 || core::$user_rights >= 6) && $mod) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '//library/index.php?act=moder">M:' . $mod . '</a></span>';
        }
        return $total;
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
        global $rootpath;
        $file = $rootpath . 'files/cache/count_users.dat';
        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $total = $res['total'];
            $new = $res['new'];
        } else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users`"), 0);
            $new = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `datereg` > '" . (time() - 86400) . "'"), 0);
            file_put_contents($file, serialize(array('total' => $total, 'new' => $new)));
        }
        if ($new) $total .= '&#160;/&#160;<span class="red">+' . $new . '</span>';
        return $total;
    }
}