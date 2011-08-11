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

switch ($mod) {
    case 'up':
        /*
        -----------------------------------------------------------------
        Передвигаем альбом на позицию вверх
        -----------------------------------------------------------------
        */
        if ($al && $user['id'] == $user_id || $rights >= 7) {
            $req = mysql_query("SELECT `sort` FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = '" . $user['id'] . "'");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                $sort = $res['sort'];
                $req = mysql_query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' AND `sort` < '$sort' ORDER BY `sort` DESC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    mysql_query("UPDATE `cms_album_cat` SET `sort` = '$sort2' WHERE `id` = '$al'");
                    mysql_query("UPDATE `cms_album_cat` SET `sort` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        break;

    case 'down':
        /*
        -----------------------------------------------------------------
        Передвигаем альбом на позицию вниз
        -----------------------------------------------------------------
        */
        if ($al && $user['id'] == $user_id || $rights >= 7) {
            $req = mysql_query("SELECT `sort` FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = '" . $user['id'] . "'");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                $sort = $res['sort'];
                $req = mysql_query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' AND `sort` > '$sort' ORDER BY `sort` ASC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    mysql_query("UPDATE `cms_album_cat` SET `sort` = '$sort2' WHERE `id` = '$al'");
                    mysql_query("UPDATE `cms_album_cat` SET `sort` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        break;
}

header('Location: album.php?act=list&user=' . $user['id']);
?>