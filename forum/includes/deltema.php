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
if ($rights == 3 || $rights >= 6) {
    if (!$id) {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    // Проверяем, существует ли тема
    $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't'");
    if (!mysql_num_rows($req)) {
        require('../incfiles/head.php');
        echo functions::display_error($lng_forum['error_topic_deleted']);
        require('../incfiles/end.php');
        exit;
    }
    $res = mysql_fetch_assoc($req);
    if (isset($_POST['submit'])) {
        $del = isset($_POST['del']) ? intval($_POST['del']) : NULL;
        if ($del == 2 && core::$user_rights == 9) {
            /*
            -----------------------------------------------------------------
            Удаляем топик
            -----------------------------------------------------------------
            */
            $req1 = mysql_query("SELECT * FROM `cms_forum_files` WHERE `topic` = '$id'");
            if (mysql_num_rows($req1)) {
                while ($res1 = mysql_fetch_assoc($req1)) {
                    unlink('../files/forum/attach/' . $res1['filename']);
                }
                mysql_query("DELETE FROM `cms_forum_files` WHERE `topic` = '$id'");
                mysql_query("OPTIMIZE TABLE `cms_forum_files`");
            }
            mysql_query("DELETE FROM `forum` WHERE `refid` = '$id'");
            mysql_query("DELETE FROM `forum` WHERE `id`='$id'");
        } elseif ($del = 1) {
            /*
            -----------------------------------------------------------------
            Скрываем топик
            -----------------------------------------------------------------
            */
            mysql_query("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '$id'");
            mysql_query("UPDATE `cms_forum_files` SET `del` = '1' WHERE `topic` = '$id'");
        }
        header('Location: index.php?id=' . $res['refid']);
    } else {
        /*
        -----------------------------------------------------------------
        Меню выбора режима удаления темы
        -----------------------------------------------------------------
        */
        require('../incfiles/head.php');
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['topic_delete'] . '</div>' .
             '<div class="rmenu"><form method="post" action="index.php?act=deltema&amp;id=' . $id . '">' .
             '<p><h3>' . $lng['delete_confirmation'] . '</h3>' .
             '<input type="radio" value="1" name="del" checked="checked"/>&#160;' . $lng['hide'] . '<br />' .
             (core::$user_rights == 9 ? '<input type="radio" value="2" name="del" />&#160;' . $lng['delete'] . '</p>' : '') .
             '<p><input type="submit" name="submit" value="' . $lng['do'] . '" /></p>' .
             '<p><a href="index.php?id=' . $id . '">' . $lng['cancel'] . '</a>' .
             '</p></form></div>' .
             '<div class="phdr">&#160;</div>';
    }
} else {
    echo functions::display_error($lng['access_forbidden']);
}