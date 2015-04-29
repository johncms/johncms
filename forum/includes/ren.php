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
    $typ = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
    $ms = mysql_fetch_assoc($typ);
    if ($ms[type] != "t") {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $nn = isset($_POST['nn']) ? functions::check($_POST['nn']) : false;
        if (!$nn) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_name'], '<a href="index.php?act=ren&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        // Проверяем, есть ли тема с таким же названием?
        $pt = mysql_query("SELECT * FROM `forum` WHERE `type` = 't' AND `refid` = '" . $ms['refid'] . "' and text='$nn' LIMIT 1");
        if (mysql_num_rows($pt) != 0) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_exists'], '<a href="index.php?act=ren&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        mysql_query("update `forum` set  text='" . $nn . "' where id='" . $id . "';");
        header("Location: index.php?id=$id");
    } else {
        /*
        -----------------------------------------------------------------
        Переименовываем тему
        -----------------------------------------------------------------
        */
        require('../incfiles/head.php');
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['topic_rename'] . '</div>' .
            '<div class="menu"><form action="index.php?act=ren&amp;id=' . $id . '" method="post">' .
            '<p><h3>' . $lng_forum['topic_name'] . '</h3>' .
            '<input type="text" name="nn" value="' . $ms['text'] . '"/></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></div>';
    }
} else {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_forbidden']);
}
