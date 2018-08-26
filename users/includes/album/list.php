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

require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Список альбомов юзера
-----------------------------------------------------------------
*/
if (isset($_SESSION['ap'])) {
    unset($_SESSION['ap']);
}
echo '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | ' . $lng['personal_2'] . '</div>';
$stmt = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' " . ($user['id'] == $user_id || $rights >= 6 ? "" : "AND `access` > 1") . " ORDER BY `sort` ASC");
$total = $stmt->rowCount();
if ($user['id'] == $user_id && $total < $max_album && empty($ban) || $rights >= 7) {
    echo '<div class="topmenu"><a href="album.php?act=edit&amp;user=' . $user['id'] . '">' . $lng_profile['album_create'] . '</a></div>';
}
echo '<div class="user"><p>' . functions::display_user($user, array('iphide' => 1,)) . '</p></div>';
if ($total) {
    $i = 0;
    while ($res = $stmt->fetch()) {
        $count = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '" . $res['id'] . "'")->fetchColumn();
        echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
            '<img src="../images/album-' . $res['access'] . '.gif" width="16" height="16" class="left" />&#160;' .
            '<a href="album.php?act=show&amp;al=' . $res['id'] . '&amp;user=' . $user['id'] . '"><b>' . functions::checkout($res['name']) . '</b></a>&#160;(' . $count . ')';
        if ($user['id'] == $user_id || $rights >= 6 || !empty($res['description'])) {
            $menu = array(
                '<a href="album.php?act=sort&amp;mod=up&amp;al=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['up'] . '</a>',
                '<a href="album.php?act=sort&amp;mod=down&amp;al=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['down'] . '</a>',
                '<a href="album.php?act=edit&amp;al=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['edit'] . '</a>',
                '<a href="album.php?act=delete&amp;al=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['delete'] . '</a>'
            );
            echo '<div class="sub">' .
                (!empty($res['description']) ? '<div class="gray">' . functions::checkout($res['description'], 1, 1) . '</div>' : '') .
                ($user['id'] == $user_id && empty($ban) || $rights >= 6 ? functions::display_menu($menu) : '') .
                '</div>';
        }
        echo '</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
