<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
if (!$al) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$req = mysql_query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al'");
if (!mysql_num_rows($req)) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$album = mysql_fetch_assoc($req);

/*
-----------------------------------------------------------------
Показываем выбранный альбом с фотографиями
-----------------------------------------------------------------
*/
echo '<div class="phdr"><a href="album.php"><b>' . $lng['photo_albums'] . '</b></a> | <a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng['personal_2'] . '</a></div>';
if ($user['id'] == $user_id || $rights >= 7)
    echo '<div class="topmenu"><a href="album.php?act=image_upload&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . $lng_profile['image_add'] . '</a></div>';
echo '<div class="user"><p>' . functions::display_user($user, array ('iphide' => 1,)) . '</p></div>' .
    '<div class="phdr">' . $lng_profile['album'] . ': <b>' . functions::checkout($album['name']) . '</b><br />' . functions::checkout($album['description'], 1) . '</div>';

/*
-----------------------------------------------------------------
Проверяем права доступа к альбому
-----------------------------------------------------------------
*/
if ($album['access'] != 2)
    unset($_SESSION['ap']);
if ($album['access'] == 1 && $user['id'] != $user_id && $rights < 6) {
    // Если доступ закрыт
    echo functions::display_error($lng['access_forbidden']) .
        '<div class="phdr"><a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a></div>';
    require('../incfiles/end.php');
    exit;
} elseif ($album['access'] == 2 && $user['id'] != $user_id && $rights < 6) {
    // Если доступ через пароль
    if (isset($_POST['password'])) {
        if ($album['password'] == trim($_POST['password']))
            $_SESSION['ap'] = $album['password'];
        else
            echo functions::display_error($lng['error_wrong_password']);
    }
    if (!isset($_SESSION['ap']) || $_SESSION['ap'] != $album['password']) {
        echo '<form action="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post"><div class="menu"><p>';
        echo $lng_profile['album_password'] . '<br />';
        echo '<input type="text" name="password"/></p>';
        echo '<p><input type="submit" name="submit" value="' . $lng['login'] . '"/></p>';
        echo '</div></form>';
        echo '<div class="phdr"><a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a></div>';
        require('../incfiles/end.php');
        exit;
    }
}
if ($img) {
    /*
    -----------------------------------------------------------------
    Предпросмотр фотографии
    -----------------------------------------------------------------
    */
    $req = mysql_query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` = '" . $user['id'] . "' AND `album_id` = '$al'");
    if (mysql_num_rows($req)) {
        $res = mysql_fetch_assoc($req);
        // Счетчик просмотров
        if(!mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_views` WHERE `user_id` = '$user_id' AND `file_id` = '$img'"), 0)){
            mysql_query("INSERT INTO `cms_album_views` SET `user_id` = '$user_id', `file_id` = '$img', `time` = '$realtime'");
            $views = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_views` WHERE `file_id` = '$img'"), 0);
            mysql_query("UPDATE `cms_album_files` SET `views` = '$views' WHERE `id` = '$img'");
        }
        echo '<div class="menu">' .
            '<a href="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '"><img src="image.php?u=' . $user['id'] . '&amp;f=' . $res['img_name'] . '" /></a>';
        if (!empty($res['description']))
            echo '<div class="gray">' . functions::smileys(functions::checkout($res['description'], 1, 1)) . '</div>';
        echo '<div class="sub">';
        if ($user['id'] == $user_id || $rights >= 6) {
            echo functions::display_menu(array (
                '<a href="album.php?act=image_edit&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['edit'] . '</a>',
                '<a href="album.php?act=image_move&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['move'] . '</a>',
                '<a href="album.php?act=image_delete&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '">' . $lng['delete'] . '</a>'
            ));
        }
        echo vote_photo($res) .
            '<div class="gray">' . $lng['count_views'] . ': ' . $res['views'] . ', ' . $lng['count_downloads'] . ': ' . $res['downloads'] . '</div>' .
            '<a href="album.php?act=comments&amp;img=' . $res['id'] . '">' . $lng['comments'] . '</a> (' . $res['comm_count'] . ')<br />' .
            '<a href="album.php?act=image_download&amp;img=' . $res['id'] . '">' . $lng['download'] . '</a>' .
            '</div></div>' .
            '<div class="phdr"><a href="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . $lng_profile['album'] . '</a></div>';
    } else {
        echo functions::display_error($lng['error_wrong_data']);
    }
} else {
    /*
    -----------------------------------------------------------------
    Просмотр альбома
    -----------------------------------------------------------------
    */
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "' AND `album_id` = '$al'"), 0);
    if ($total > $kmess)
        echo '<div class="topmenu">' . functions::display_pagination('album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</div>';
    if ($total) {
        $req = mysql_query("SELECT * FROM `cms_album_files` WHERE `user_id` = '" . $user['id'] . "' AND `album_id` = '$al' ORDER BY `time` DESC LIMIT $start, $kmess");
        while ($res = mysql_fetch_assoc($req)) {
            echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
                '<a href="album.php?act=show&amp;al=' . $al . '&amp;img=' . $res['id'] . '&amp;user=' . $user['id'] . '"><img src="../files/users/album/' . $user['id'] . '/' . $res['tmb_name'] . '" /></a>';
            if (!empty($res['description']))
                echo '<div class="gray">' . functions::smileys(functions::checkout($res['description'], 1)) . '</div>';
            echo '<div class="sub">' . vote_photo($res) .
                '<div class="gray">' . $lng['count_views'] . ': ' . $res['views'] . ', ' . $lng['count_downloads'] . ': ' . $res['downloads'] . '</div>' .
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
        echo '<div class="topmenu">' . functions::display_pagination('album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</div>' .
            '<p><form action="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
            '</form></p>';
    }
    echo '<p><a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng_profile['album_list'] . '</a></p>';
}
?>