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
    if (isset($_GET['yes']) && $rights == 9) {
        // Удаляем прикрепленные файлы
        $req1 = mysql_query("SELECT * FROM `cms_forum_files` WHERE `topic` = '$id'");
        if (mysql_num_rows($req1)) {
            while ($res1 = mysql_fetch_array($req1)) {
                unlink('files/' . $res1['filename']);
            }
            mysql_query("DELETE FROM `cms_forum_files` WHERE `topic` = '$id'");
            mysql_query("OPTIMIZE TABLE `cms_forum_files`");
        }
        // Удаляем посты топика
        mysql_query("DELETE FROM `forum` WHERE `refid` = '$id'");
        // Удаляем топик
        mysql_query("DELETE FROM `forum` WHERE `id`='$id'");
        header('Location: ?id=' . $res['refid']);
    } elseif (isset($_GET['hid']) || isset($_GET['yes']) && $rights < 9) {
        // Скрываем топик
        mysql_query("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '$id'");
        // Скрываем прикрепленные файлы
        mysql_query("UPDATE `cms_forum_files` SET `del` = '1' WHERE `topic` = '$id'");
        header('Location: ?id=' . $res['refid']);
    }
    require('../incfiles/head.php');
    echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['topic_delete'] . '</div>' .
        '<div class="rmenu"><p>' . $lng['delete_confirmation'] . '</p>' .
        '<p><a href="index.php?id=' . $id . '">' . $lng['cancel'] . '</a> | ' .
        '<a href="index.php?act=deltema&amp;id=' . $id . '&amp;yes">' . $lng['delete'] . '</a>';
    if ($rights == 9 && $res['close'] != 1)
        echo ' | <a href="index.php?act=deltema&amp;id=' . $id . '&amp;hid">' . $lng['hide'] . '</a>';
    echo '</p></div>';
    echo '<div class="phdr">&#160;</div>';
} else {
    echo functions::display_error($lng['access_forbidden']);
}
?>