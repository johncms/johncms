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
            $total .= '&#160;/&#160;<span class="red"><a href="' . App::getContainer()->get('config')['johncms']['homeurl'] . '/library/index.php?act=new">+' . $new . '</a></span>';
        }
        if ((core::$user_rights == 5 || core::$user_rights >= 6) && $mod) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . App::getContainer()->get('config')['johncms']['homeurl'] . '/library/index.php?act=premod">M:' . $mod . '</a></span>';
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
        $config = App::getContainer()->get('config')['johncms'];

        return (core::$user_id || $config['active'] ? '<a href="' . $config['homeurl'] . '/users/index.php?act=online">' . functions::image('menu_online.png') . $users . ' / ' . $guests . '</a>' : core::$lng['online'] . ': ' . $users . ' / ' . $guests);
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
