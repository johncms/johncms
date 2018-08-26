<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');
$textl = $lng['users_list'];
$headmod = 'userlist';
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Выводим список пользователей
-----------------------------------------------------------------
*/
$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `preg` = 1")->fetchColumn();
echo '<div class="phdr"><a href="index.php"><b>' . $lng['community'] . '</b></a> | ' . $lng['users_list'] . '</div>';
if ($total > $kmess)
    echo '<div class="topmenu">' . functions::display_pagination('index.php?act=userlist&amp;', $start, $total, $kmess) . '</div>';
$stmt = $db->query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `preg` = 1 ORDER BY `datereg` DESC LIMIT $start, $kmess");
$i = 0;
while ($res = $stmt->fetch()) {
    echo ++$i % 2 ? '<div class="list2">' : '<div class="list1">';
    echo functions::display_user($res) . '</div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('index.php?act=userlist&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="index.php?act=userlist" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}
echo '<p><a href="search.php">' . $lng['search_user'] . '</a><br />' .
    '<a href="index.php">' . $lng['back'] . '</a></p>';