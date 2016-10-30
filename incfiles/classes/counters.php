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
