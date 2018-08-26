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
Перемещение картинки в другой альбом
-----------------------------------------------------------------
*/
if ($img && $user['id'] == $user_id || $rights >= 6) {
    $stmt = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` = '" . $user['id'] . "'");
    if ($stmt->rowCount()) {
        $image = $stmt->fetch();
        echo '<div class="phdr"><a href="album.php?act=show&amp;al=' . $image['album_id'] . '&amp;user=' . $user['id'] . '"><b>' . $lng['photo_album'] . '</b></a> | ' . $lng_profile['image_move'] . '</div>';
        if (isset($_POST['submit'])) {
            $stmt = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = '" . $user['id'] . "'");
            if ($stmt->rowCount()) {
                $res_a = $stmt->fetch();
                $db->exec("UPDATE `cms_album_files` SET
                    `album_id` = '$al',
                    `access` = '" . $res_a['access'] . "'
                    WHERE `id` = '$img'
                ");
                echo '<div class="gmenu"><p>' . $lng_profile['image_moved'] . '<br />' .
                    '<a href="album.php?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . $lng['continue'] . '</a></p></div>';
            } else {
                echo functions::display_error($lng['error_wrong_data']);
            }
        } else {
            $stmt = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' AND `id` != '" . $image['album_id'] . "' ORDER BY `sort` ASC");
            if ($stmt->rowCount()) {
                echo '<form action="album.php?act=image_move&amp;img=' . $img . '&amp;user=' . $user['id'] . '" method="post">' .
                    '<div class="menu"><p><h3>' . $lng_profile['album_select'] . '</h3>' .
                    '<select name="al">';
                while ($res = $stmt->fetch()) {
                    echo '<option value="' . $res['id'] . '">' . functions::checkout($res['name']) . '</option>';
                }
                echo '</select></p>' .
                    '<p><input type="submit" name="submit" value="' . $lng['move'] . '"/></p>' .
                    '</div></form>' .
                    '<div class="phdr"><a href="album.php?act=show&amp;al=' . $image['album_id'] . '&amp;user=' . $user['id'] . '">' . $lng['cancel'] . '</a></div>';
            } else {
                echo functions::display_error($lng_profile['image_move_error'], '<a href="album.php?act=list&amp;user=' . $user['id'] . '">' . $lng['continue'] . '</a>');
            }
        }
    } else {
        echo functions::display_error($lng['error_wrong_data']);
    }
}
