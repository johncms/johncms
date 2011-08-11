<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNADM') or die('Error: restricted access');
$error = array ();
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
$search = $search_post ? $search_post : $search_get;
if (isset($_GET['ip']))
    $search = trim($_GET['ip']);
$menu = array (
    (!$mod ? '<b>' . $lng['ip_actual'] . '</b>' : '<a href="index.php?act=search_ip&amp;search=' . rawurlencode($search) . '">' . $lng['ip_actual'] . '</a>'),
    ($mod == 'history' ? '<b>' . $lng['ip_history'] . '</b>' : '<a href="index.php?act=search_ip&amp;mod=history&amp;search=' . rawurlencode($search) . '">' . $lng['ip_history'] . '</a>')
);
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['ip_search'] . '</div>' .
    '<div class="topmenu">' . functions::display_menu($menu) . '</div>' .
    '<form action="index.php?act=search_ip" method="post"><div class="gmenu"><p>' .
    '<input type="text" name="search" value="' . functions::checkout($search) . '" />' .
    '<input type="submit" value="' . $lng['search'] . '" name="submit" /><br />' .
    '</p></div></form>';
if ($search) {
    if (strstr($search, '-')) {
        /*
        -----------------------------------------------------------------
        Обрабатываем диапазон адресов
        -----------------------------------------------------------------
        */
        $array = explode('-', $search);
        $ip = trim($array[0]);
        if (!core::ip_valid($ip))
            $error[] = $lng['error_firstip'];
        else
            $ip1 = ip2long($ip);
        $ip = trim($array[1]);
        if (!core::ip_valid($ip))
            $error[] = $lng['error_secondip'];
        else
            $ip2 = ip2long($ip);
    } elseif (strstr($search, '*')) {
        /*
        -----------------------------------------------------------------
        Обрабатываем адреса с маской
        -----------------------------------------------------------------
        */
        $array = explode('.', $search);
        for ($i = 0; $i < 4; $i++) {
            if (!isset($array[$i]) || $array[$i] == '*') {
                $ipt1[$i] = '0';
                $ipt2[$i] = '255';
            } elseif (is_numeric($array[$i]) && $array[$i] >= 0 && $array[$i] <= 255) {
                $ipt1[$i] = $array[$i];
                $ipt2[$i] = $array[$i];
            } else {
                $error = $lng['error_address'];
            }
            $ip1 = ip2long($ipt1[0] . '.' . $ipt1[1] . '.' . $ipt1[2] . '.' . $ipt1[3]);
            $ip2 = ip2long($ipt2[0] . '.' . $ipt2[1] . '.' . $ipt2[2] . '.' . $ipt2[3]);
        }
    } else {
        /*
        -----------------------------------------------------------------
        Обрабатываем одиночный адрес
        -----------------------------------------------------------------
        */
        if (!core::ip_valid($search)) {
            $error = $lng['error_address'];
        } else {
            $ip1 = ip2long($search);
            $ip2 = $ip1;
        }
    }
}
if ($search && !$error) {
    /*
    -----------------------------------------------------------------
    Выводим результаты поиска
    -----------------------------------------------------------------
    */
    echo '<div class="phdr">' . $lng['search_results'] . '</div>';
    if ($mod == 'history')
        $total = mysql_result(mysql_query("SELECT COUNT(DISTINCT `cms_users_iphistory`.`user_id`) FROM `cms_users_iphistory` WHERE `ip` BETWEEN $ip1 AND $ip2 OR `ip_via_proxy` BETWEEN $ip1 AND $ip2"), 0);
    else
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `ip` BETWEEN $ip1 AND $ip2 OR `ip_via_proxy` BETWEEN $ip1 AND $ip2"), 0);
    if ($total > $kmess) {
        echo '<div class="topmenu">' . functions::display_pagination('index.php?act=search_ip' . ($mod == 'history' ? '&amp;mod=history' : '') . '&amp;search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>';
    }
    if ($total) {
        if ($mod == 'history') {
            $req = mysql_query("SELECT `cms_users_iphistory`.*, `users`.`name`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`, `users`.`browser`
                FROM `cms_users_iphistory` LEFT JOIN `users` ON `cms_users_iphistory`.`user_id` = `users`.`id`
                WHERE `cms_users_iphistory`.`ip` BETWEEN $ip1 AND $ip2 OR `cms_users_iphistory`.`ip_via_proxy` BETWEEN $ip1 AND $ip2
                GROUP BY `users`.`id`
                ORDER BY `ip` ASC, `name` ASC LIMIT $start, $kmess
            ");
        } else {
            $req = mysql_query("SELECT * FROM `users`
            WHERE `ip` BETWEEN $ip1 AND $ip2 OR `ip_via_proxy` BETWEEN $ip1 AND $ip2
            ORDER BY `ip` ASC, `name` ASC LIMIT $start, $kmess");
        }
        $i = 0;
        while (($res = mysql_fetch_assoc($req)) !== false) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo functions::display_user($res, array ('iphist' => 1));
            echo '</div>';
            ++$i;
        }
    } else {
        echo '<div class="menu"><p>' . $lng['not_found'] . '</p></div>';
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        // Навигация по страницам
        echo '<div class="topmenu">' . functions::display_pagination('index.php?act=search_ip' . ($mod == 'history' ? '&amp;mod=history' : '') . '&amp;search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>' .
             '<p><form action="index.php?act=search_ip' . ($mod == 'history' ? '&amp;mod=history' : '') . '&amp;search=' . urlencode($search) . '" method="post">' .
             '<input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
             '</form></p>';
    }
    echo '<p><a href="index.php?act=search_ip">' . $lng['search_new'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
} else {
    // Выводим сообщение об ошибке
    if ($error)
        echo functions::display_error($error);
    // Инструкции для поиска
    echo '<div class="phdr">' . $lng['search_ip_help'] . '</div>';
    echo '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
}
?>