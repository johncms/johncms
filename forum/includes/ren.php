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
    $error = true;
    if ($id) {
        $stmt = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't' LIMIT 1");
        if ($stmt->rowCount()) {
            $error = false;
            $ms = $stmt->fetch();
        }
    }
    if ($error) {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $nn = isset($_POST['nn']) ? trim(mb_substr(functions::checkin($_POST['nn'], 1), 0, 255)) : false;
        if (!$nn) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_name'], '<a href="index.php?act=ren&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        // Проверяем, есть ли тема с таким же названием?
        $stmt = $db->prepare("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `refid` = '" . $ms['refid'] . "' and `text`= ?");
        $stmt->execute([
            $nn
        ]);
        if ($stmt->fetchColumn()) {
            require('../incfiles/head.php');
            echo functions::display_error($lng_forum['error_topic_exists'], '<a href="index.php?act=ren&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $stmt = $db->prepare("update `forum` set  text= ?  where id='" . $id . "' LIMIT 1;");
        $stmt->execute([
            $nn
        ]);
        header("Location: index.php?id=$id"); exit;
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
            '<input type="text" name="nn" value="' . _e($ms['text']) . '"/></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></div>';
    }
} else {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_forbidden']);
}
