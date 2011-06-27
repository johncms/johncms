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

// Проверяем права доступа
if ($rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['users_clean'] . '</div>';

switch ($mod) {
    case 1:
        mysql_query("DELETE FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'
        ");
        mysql_query("OPTIMIZE TABLE `users`");
        echo '<div class="rmenu"><p>' . $lng['dead_profiles_deleted'] . '</p><p><a href="index.php">' . $lng['continue'] . '</a></p></div>';
        break;

    default:
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users`
            WHERE `datereg` < '" . (time() - 2592000 * 6) . "'
            AND `lastdate` < '" . (time() - 2592000 * 5) . "'
            AND `postforum` = '0'
            AND `postguest` < '10'
            AND `komm` < '10'"), 0);
        echo '<div class="menu"><form action="index.php?act=usr_clean&amp;mod=1" method="post">' .
             '<p><h3>' . $lng['dead_profiles'] . '</h3>' . $lng['dead_profiles_desc'] . '</p>' .
             '<p>' . $lng['total'] . ': <b>' . $total . '</b></p>' .
             '<p><input type="submit" name="submit" value="' . $lng['delete'] . '"/></p></form></div>' .
             '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
}

?>