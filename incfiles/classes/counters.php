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
    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return string
     */
    public static function album()
    {
        $file = ROOTPATH . 'files/cache/count_album.dat';
        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $album = $res['album'];
            $photo = $res['photo'];
            $new = $res['new'];
            $new_adm = $res['new_adm'];
        } else {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);

            $album = $db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn();
            $photo = $db->query('SELECT COUNT(*) FROM `cms_album_files`')->fetchColumn();
            $new = $db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` = 4')->fetchColumn();
            $new_adm = $db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . (time() - 259200) . ' AND `access` > 1')->fetchColumn();
            file_put_contents($file, serialize(['album' => $album, 'photo' => $photo, 'new' => $new, 'new_adm' => $new_adm]));
        }

        $newcount = 0;
        if (core::$user_rights >= 6 && $new_adm) {
            $newcount = $new_adm;
        } elseif ($new) {
            $newcount = $new;
        }

        return $album . '&#160;/&#160;' . $photo .
        ($newcount ? '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/album/index.php?act=top">+' . $newcount . '</a></span>' : '');
    }

    /**
     * Статистика загрузок
     *
     * @return string
     */
    public static function downloads()
    {
        $file = ROOTPATH . 'files/cache/count_downloads.dat';
        if (file_exists($file) && filemtime($file) > (time() - 900)) {
            $res = unserialize(file_get_contents($file));
            $total = $res['total'];
            $new = $res['new'];
        } else {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);

            $total = $db->query("SELECT COUNT(*) FROM `download` WHERE `type` = 'file'")->fetchColumn();
            $new = $db->query("SELECT COUNT(*) FROM `download` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'file'")->fetchColumn();
            file_put_contents($file, serialize(['total' => $total, 'new' => $new]));
        }
        if ($new) {
            $total .= '&#160;/&#160;<span class="red"><a href="/download/?act=new">+' . $new . '</a></span>';
        }

        return $total;
    }

    /**
     * Статистика Форума
     *
     * @return string
     */
    public static function forum()
    {
        $file = ROOTPATH . 'files/cache/count_forum.dat';
        $new = '';
        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $top = $res['top'];
            $msg = $res['msg'];
        } else {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);

            $top = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` != '1'")->fetchColumn();
            $msg = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` != '1'")->fetchColumn();
            file_put_contents($file, serialize(['top' => $top, 'msg' => $msg]));
        }

        if (core::$user_id && ($new_msg = self::forumNew()) > 0) {
            $new = '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/forum/index.php?act=new">+' . $new_msg . '</a></span>';
        }

        return $top . '&#160;/&#160;' . $msg . $new;
    }

    /**
     * Счетчик непрочитанных тем на форуме
     *
     * $mod = 0   Возвращает число непрочитанных тем
     * $mod = 1   Выводит ссылки на непрочитанное
     *
     * @param int $mod
     * @return bool|int|string
     */
    public static function forumNew($mod = 0)
    {
        if (core::$user_id) {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);

            $total = $db->query("SELECT COUNT(*) FROM `forum`
                LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . core::$user_id . "'
                WHERE `forum`.`type` = 't'" . (core::$user_rights >= 7 ? "" : " AND `forum`.`close` != 1") . "
                AND (`cms_forum_rdm`.`topic_id` IS NULL
                OR `forum`.`time` > `cms_forum_rdm`.`time`)")->fetchColumn();

            if ($mod) {
                return '<a href="index.php?act=new&amp;do=period">' . core::$lng['show_for_period'] . '</a>' .
                ($total ? '<br/><a href="index.php?act=new">' . core::$lng['unread'] . '</a>&#160;<span class="red">(<b>' . $total . '</b>)</span>' : '');
            } else {
                return $total;
            }
        } else {
            if ($mod) {
                return '<a href="index.php?act=new">' . core::$lng['last_activity'] . '</a>';
            } else {
                return false;
            }
        }
    }

    /**
     * Статистика галлереи
     *
     * $mod = 1    будет выдавать только колличество новых картинок
     *
     * @param int $mod
     * @return string
     */
    public static function gallery($mod = 0)
    {
        /** @var PDO $db */
        $db = App::getContainer()->get(PDO::class);

        $new = $db->query("SELECT COUNT(*) FROM `gallery` WHERE `time` > '" . (time() - 259200) . "' AND `type` = 'ft'")->fetchColumn();

        if ($mod == 0) {
            $total = $db->query("SELECT COUNT(*) FROM `gallery` WHERE `type` = 'ft'")->fetchColumn();
            $out = $total;

            if ($new > 0) {
                $out .= '&#160;/&#160;<span class="red"><a href="/gallery/index.php?act=new">+' . $new . '</a></span>';
            }
        } else {
            $out = $new;
        }

        return $out;
    }

    /**
     * Статистика гостевой
     *
     * $mod = 1    колличество новых в гостевой
     * $mod = 2    колличество новых в Админ-Клубе
     *
     * @param int $mod
     * @return int|string
     */
    public static function guestbook($mod = 0)
    {
        /** @var PDO $db */
        $db = App::getContainer()->get(PDO::class);

        $count = 0;
        switch ($mod) {
            case 1:
                $count = $db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=0 AND `time` > ' . (time() - 86400))->fetchColumn();
                break;

            case 2:
                if (core::$user_rights >= 1) {
                    $count = $db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=1 AND `time` > ' . (time() - 86400))->fetchColumn();
                    //$count = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `adm`='1' AND `time` > '" . (time() - 86400) . "'"), 0);
                }
                break;

            default:
                $count = $db->query('SELECT COUNT(*) FROM `guest` WHERE `adm` = 0 AND `time` > ' . (time() - 86400))->fetchColumn();

                if (core::$user_rights >= 1) {
                    $adm = $db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=\'1\' AND `time`> ' . (time() - 86400))->fetchColumn();
                    $count = $count . '&#160;/&#160;<span class="red"><a href="guestbook/index.php?act=ga&amp;do=set">' . $adm . '</a></span>';
                }
        }

        return $count;
    }

    /**
     * Статистика библиотеки
     *
     * @return string
     */
    public static function library()
    {
        $file = ROOTPATH . 'files/cache/count_library.dat';
        if (file_exists($file) && filemtime($file) > (time() - 3200)) {
            $res = unserialize(file_get_contents($file));
            $total = $res['total'];
            $new = $res['new'];
            $mod = $res['mod'];
        } else {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);

            $total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 1')->fetchColumn();
            $new = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `time` > ' . (time() - 259200) . ' AND `premod` = 1')->fetchColumn();
            $mod = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();

            file_put_contents($file, serialize(['total' => $total, 'new' => $new, 'mod' => $mod]));
        }
        if ($new) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/library/index.php?act=new">+' . $new . '</a></span>';
        }
        if ((core::$user_rights == 5 || core::$user_rights >= 6) && $mod) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . core::$system_set['homeurl'] . '/library/index.php?act=premod">M:' . $mod . '</a></span>';
        }

        return $total;
    }

    /**
     * Счетчик посетителей онлайн
     *
     * @return string
     */
    public static function online()
    {
        /** @var PDO $db */
        $db = App::getContainer()->get(PDO::class);

        $users = $db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300))->fetchColumn();
        $guests = $db->query('SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > ' . (time() - 300))->fetchColumn();

        return (core::$user_id || core::$system_set['active'] ? '<a href="' . core::$system_set['homeurl'] . '/users/index.php?act=online">' . functions::image('menu_online.png') . $users . ' / ' . $guests . '</a>' : core::$lng['online'] . ': ' . $users . ' / ' . $guests);
    }

    /**
     * Количество зарегистрированных пользователей
     *
     * @return string
     */
    public static function users()
    {
        $file = ROOTPATH . 'files/cache/count_users.dat';

        if (file_exists($file) && filemtime($file) > (time() - 600)) {
            $res = unserialize(file_get_contents($file));
            $total = $res['total'];
            $new = $res['new'];
        } else {
            /** @var PDO $db */
            $db = App::getContainer()->get(PDO::class);

            $total = $db->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
            $new = $db->query('SELECT COUNT(*) FROM `users` WHERE `datereg` > ' . (time() - 86400))->fetchColumn();

            file_put_contents($file, serialize(['total' => $total, 'new' => $new]));
        }

        return $total . ($new ? '&#160;/&#160;<span class="red">+' . $new . '</span>' : '');
    }
}