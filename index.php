<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

define('_IN_JOHNCMS', 1);

require('incfiles/core.php');

if (isset($_SESSION['ref']))
    unset($_SESSION['ref']);
if (isset($_GET['err']))
    $act = 404;

switch ($act) {
    case '404':
        /*
        -----------------------------------------------------------------
        Сообщение об ошибке 404
        -----------------------------------------------------------------
        */
        $headmod = 'error404';
        require('incfiles/head.php');
        echo functions::display_error($lng['error_404']);
        break;

    default:
        /*
        -----------------------------------------------------------------
        Главное меню сайта
        -----------------------------------------------------------------
        */
        if (isset($_SESSION['ref']))
            unset($_SESSION['ref']);
        $headmod = 'mainpage';
        require('incfiles/head.php');
        include 'pages/mainmenu.php';

        /*
        -----------------------------------------------------------------
        Карта сайта
        -----------------------------------------------------------------
        */
        if (isset($set['sitemap'])) {
            $set_map = unserialize($set['sitemap']);
            if (($set_map['forum'] || $set_map['lib']) && ($set_map['users'] || !$user_id) && ($set_map['browsers'] || !$is_mobile)) {
                $map = new sitemap();
                echo '<div class="sitemap">' . $map->site() . '</div>';
            }
        }
}

require('incfiles/end.php');