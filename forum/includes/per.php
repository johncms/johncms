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
    $typ = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't'");
    if (!mysql_num_rows($typ)) {
        require('../incfiles/head.php');
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $razd = isset($_POST['razd']) ? abs(intval($_POST['razd'])) : false;
        if (!$razd) {
            require('../incfiles/head.php');
            echo functions::display_error($lng['error_wrong_data']);
            require('../incfiles/end.php');
            exit;
        }
        $typ1 = mysql_query("SELECT * FROM `forum` WHERE `id` = '$razd' AND `type` = 'r'");
        if (!mysql_num_rows($typ1)) {
            require('../incfiles/head.php');
            echo functions::display_error($lng['error_wrong_data']);
            require('../incfiles/end.php');
            exit;
        }
        mysql_query("UPDATE `forum` SET
            `refid` = '$razd'
            WHERE `id` = '$id'
        ");
        header("Location: index.php?id=$id");
    } else {
        /*
        -----------------------------------------------------------------
        Перенос темы
        -----------------------------------------------------------------
        */
        $ms = mysql_fetch_assoc($typ);
        require('../incfiles/head.php');
        if (empty($_GET['other'])) {
            $rz = mysql_query("select * from `forum` where id='" . $ms['refid'] . "';");
            $rz1 = mysql_fetch_assoc($rz);
            $other = $rz1['refid'];
        } else {
            $other = intval(functions::check($_GET['other']));
        }
        $fr = mysql_query("select * from `forum` where id='" . $other . "';");
        $fr1 = mysql_fetch_assoc($fr);
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['topic_move'] . '</div>' .
            '<form action="index.php?act=per&amp;id=' . $id . '" method="post">' .
            '<div class="gmenu"><p>' .
            '<h3>' . $lng['category'] . '</h3>' . $fr1['text'] . '</p>' .
            '<p><h3>' . $lng['section'] . '</h3>' .
            '<select name="razd">';
        $raz = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$other' AND `type` = 'r' AND `id` != '" . $ms['refid'] . "' ORDER BY `realid` ASC");
        while ($raz1 = mysql_fetch_assoc($raz)) {
            echo '<option value="' . $raz1['id'] . '">' . $raz1['text'] . '</option>';
        }
        echo '</select></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['move'] . '"/></p>' .
            '</div></form>' .
            '<div class="phdr">' . $lng_forum['other_categories'] . '</div>';
        $frm = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$other' ORDER BY `realid` ASC");
        while ($frm1 = mysql_fetch_assoc($frm)) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo '<a href="index.php?act=per&amp;id=' . $id . '&amp;other=' . $frm1['id'] . '">' . $frm1['text'] . '</a></div>';
            ++$i;
        }
        echo '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
    }
}
?>