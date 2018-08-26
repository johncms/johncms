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

require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Удалить альбом
-----------------------------------------------------------------
*/
if ($al && $user['id'] == $user_id || $rights >= 6) {
    $stmt = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = '" . $user['id'] . "' LIMIT 1");
    if ($stmt->rowCount()) {
        $res_a = $stmt->fetch();
        echo '<div class="phdr"><a href="album.php?act=list&amp;user=' . $user['id'] . '"><b>' . $lng['photo_album'] . '</b></a> | ' . $lng['delete'] . '</div>';
        if (isset($_POST['submit'])) {
            $stmt = $db->query("SELECT * FROM `cms_album_files` WHERE `album_id` = '" . $res_a['id'] . "'");
            while ($res = $stmt->fetch()) {
                // Удаляем файлы фотографий
                @unlink('../files/users/album/' . $user['id'] . '/' . $res['img_name']);
                @unlink('../files/users/album/' . $user['id'] . '/' . $res['tmb_name']);
                // Удаляем записи из таблицы голосований
                $db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '" . $res['id'] . "'");
                // Удаляем комментарии
                $db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = '" . $res['id'] . "'");
            }
            // Удаляем записи из таблиц
            $db->exec("DELETE FROM `cms_album_files` WHERE `album_id` = '" . $res_a['id'] . "'");
            $db->exec("DELETE FROM `cms_album_cat` WHERE `id` = '" . $res_a['id'] . "'");
            $db->query("OPTIMIZE TABLE `cms_album_cat`, `cms_album_votes`, `cms_album_files`, `cms_album_comments`");
            echo '<div class="menu"><p>' . $lng_profile['album_deleted'] . '<br />' .
                '<a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng['continue'] . '</a></p></div>';
        } else {
            echo '<div class="rmenu"><form action="album.php?act=delete&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post">' .
                '<p>' . $lng_profile['album'] . ': <b>' . functions::checkout($res_a['name']) . '</b></p>' .
                '<p>' . $lng_profile['album_delete_warning'] . '</p>' .
                '<p><input type="submit" name="submit" value="' . $lng['delete'] . '"/></p>' .
                '</form></div>' .
                '<div class="phdr"><a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng['cancel'] . '</a></div>';
        }
    } else {
        echo functions::display_error($lng['error_wrong_data']);
    }
}
?>