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

if (!$al) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$stmt = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al'");
if (!$stmt->rowCount()) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$album = $stmt->fetch();
$view = isset($_GET['view']);

/*
-----------------------------------------------------------------
Показываем выбранный альбом с фотографиями
-----------------------------------------------------------------
*/
echo '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | <a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng['personal_2'] . '</a></div>';
if ($user['id'] == $user_id && empty($ban) || $rights >= 7) {
    echo '<div class="topmenu"><a href="album.php?act=image_upload&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . $lng_profile['image_add'] . '</a></div>';
}
echo '<div class="user"><p>' . functions::display_user($user) . '</p></div>' .
    '<div class="phdr">' . $lng_profile['album'] . ': ' .
    ($view ? '<a href="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '"><b>' . functions::checkout($album['name']) . '</b></a>' : '<b>' . functions::checkout($album['name']) . '</b>');

if (!empty($album['description'])) {
    echo '<div class="sub">' . functions::checkout($album['description'], 1) . '</div>';
}

echo '</div>';

/*
-----------------------------------------------------------------
Проверяем права доступа к альбому
-----------------------------------------------------------------
*/
if ($album['access'] != 2) {
    unset($_SESSION['ap']);
}
if ($album['access'] == 1
    && $user['id'] != $user_id
    && $rights < 7
) {
    // Доступ закрыт
    echo functions::display_error($lng['access_forbidden'], '<a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a>');
    require('../incfiles/end.php');
    exit;
} elseif ($album['access'] == 2
    && $user['id'] != $user_id
    && $rights < 7
) {
    // Доступ через пароль
    if (isset($_POST['password'])) {
        if ($album['password'] == trim($_POST['password'])) {
            $_SESSION['ap'] = $album['password'];
        } else {
            echo functions::display_error($lng['error_wrong_password']);
        }
    }
    if (!isset($_SESSION['ap']) || $_SESSION['ap'] != $album['password']) {
        echo '<form action="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post"><div class="menu"><p>' .
            $lng_profile['album_password'] . '<br />' .
            '<input type="text" name="password"/></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['login'] . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a></div>';
        require('../incfiles/end.php');
        exit;
    }
} elseif ($album['access'] == 3
    && $user['id'] != $user_id
    && $rights < 6
    && !functions::is_friend($user['id'])
) {
    // Доступ только для друзей
    echo functions::display_error($lng_profile['friends_only'], '<a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Просмотр альбома и фотографий
-----------------------------------------------------------------
*/
if ($view) {
    $kmess = 1;
    $start = isset($_REQUEST['page']) ? $page - 1 : ($db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '$al' AND `id` > '$img'")->fetchColumn());
    // Обрабатываем ссылку для возврата
    if (empty($_SESSION['ref']))
        $_SESSION['ref'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
} else {
    unset($_SESSION['ref']);
}
$total = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '$al'")->fetchColumn();
if ($total > $kmess)
    echo '<div class="topmenu">' . functions::display_pagination('album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;' . ($view ? 'view&amp;' : ''), $start, $total, $kmess) . '</div>';
if ($total) {
    $stmt = $db->query("SELECT * FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "' AND `album_id` = '$al' ORDER BY `id` DESC LIMIT $start, $kmess");
    $i = 0;
    while ($res = $stmt->fetch()) {
        echo($i % 2 ? '<div class="list2">' : '<div class="list1">');
        if ($view) {
            /*
            -----------------------------------------------------------------
            Предпросмотр отдельного изображения
            -----------------------------------------------------------------
            */
            if ($user['id'] == $user_id && isset($_GET['profile'])) {
                copy(
                    '../files/users/album/' . $user['id'] . '/' . $res['tmb_name'],
                    '../files/users/photo/' . $user_id . '_small.jpg'
                );
                copy(
                    '../files/users/album/' . $user['id'] . '/' . $res['img_name'],
                    '../files/users/photo/' . $user_id . '.jpg'
                );
                echo '<span class="green"><b>' . $lng_profile['photo_profile_ok'] . '</b></span><br />';
            }
            echo '<a href="' . $_SESSION['ref'] . '"><img src="image.php?u=' . $user['id'] . '&amp;f=' . $res['img_name'] . '" /></a>';
            // Счетчик просмотров
            if (!$db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `user_id` = '$user_id' AND `file_id` = '" . $res['id'] . "'")->fetchColumn()) {
                $db->exec("INSERT INTO `cms_album_views` SET `user_id` = '$user_id', `file_id` = '" . $res['id'] . "', `time` = '" . time() . "'");
                $views = $db->query("SELECT COUNT(*) FROM `cms_album_views` WHERE `file_id` = '" . $res['id'] . "'")->fetchColumn();
                $db->exec("UPDATE `cms_album_files` SET `views` = '$views' WHERE `id` = '" . $res['id'] . "'");
            }
        } else {
            /*
            -----------------------------------------------------------------
            Предпросмотр изображения в списке
            -----------------------------------------------------------------
            */
            echo '<a href="album.php?act=show&amp;al=' . $al . '&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '&amp;view"><img src="../files/users/album/' . $user['id'] . '/' . $res['tmb_name'] . '" /></a>';
        }
        if (!empty($res['description']))
            echo '<div class="gray">' . functions::smileys(functions::checkout($res['description'], 1)) . '</div>';
        echo '<div class="sub">';
        if ($user['id'] == $user_id || core::$user_rights >= 6) {
            echo functions::display_menu(array(
                '<a href="album.php?act=image_edit&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['edit'] . '</a>',
                '<a href="album.php?act=image_move&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['move'] . '</a>',
                '<a href="album.php?act=image_delete&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['delete'] . '</a>'
            ));
            if ($user['id'] == $user_id && $view)
                echo ' | <a href="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;view&amp;img=' . $res['id'] . '&amp;profile">' . $lng_profile['photo_profile'] . '</a>';
        }
        echo vote_photo($res) .
            '<div class="gray">' . $lng['count_views'] . ': ' . $res['views'] . ', ' . $lng['count_downloads'] . ': ' . $res['downloads'] . '</div>' .
            '<div class="gray">' . $lng['date'] . ': ' . functions::display_date($res['time']) . '</div>' .
            '<a href="album.php?act=comments&amp;img=' . $res['id'] . '">' . $lng['comments'] . '</a> (' . $res['comm_count'] . ')<br />' .
            '<a href="album.php?act=image_download&amp;img=' . $res['id'] . '">' . $lng['download'] . '</a>' .
            '</div></div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<div class="topmenu">' . functions::display_pagination('album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;' . ($view ? 'view&amp;' : ''), $start, $total, $kmess) . '</div>' .
        '<p><form action="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . ($view ? '&amp;view' : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}
echo '<p><a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a></p>';