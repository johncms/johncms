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

$textl = $lng['birthday_men'];
$headmod = 'birth';
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Выводим список именинников
-----------------------------------------------------------------
*/
echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['birthday_men'] . '</div>';
$shift = (core::$system_set['timeshift'] + core::$user_set['timeshift']) * 3600;
$total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time() + $shift) . "' AND `monthb` = '" . date('n', time() + $shift) . "' AND `preg` = '1'"), 0);
if ($total) {
    $req = mysql_query("SELECT * FROM `users` WHERE `dayb` = '" . date('j', time() + $shift) . "' AND `monthb` = '" . date('n', time() + $shift) . "' AND `preg` = '1' LIMIT $start, $kmess");
    while ($res = mysql_fetch_assoc($req)) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo functions::display_user($res) . '</div>';
        ++$i;
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        echo'<p>' . functions::display_pagination('index.php?act=birth&amp;', $start, $total, $kmess) . '</p>' .
            '<p><form action="index.php?act=birth" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
            '</form></p>';
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<p><a href="index.php">' . $lng['back'] . '</a></p>';