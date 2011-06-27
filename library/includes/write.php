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
if (!$id) {
    echo "";
    require_once('../incfiles/end.php');
    exit;
}

//TODO: Переделать на новый антиспам
// Проверка на спам
$old = ($rights > 0) ? 5 : 60;
if ($datauser['lastpost'] > (time() - $old)) {
    require_once('../incfiles/head.php');
    echo '<p>' . $lng['error_flood'] . ' ' . $old . ' ' . $lng['sec'] . '<br/><br/><a href ="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
    require_once('../incfiles/end.php');
    exit;
}

$typ = mysql_query("select * from `lib` where id='" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($id != 0 && $ms['type'] != "cat") {
    echo "";
    require_once('../incfiles/end.php');
    exit;
}
if ($ms['ip'] == 0) {
    if (($rights == 5 || $rights >= 6) || ($ms['soft'] == 1 && !empty($_SESSION['uid']))) {
        if (isset($_POST['submit'])) {
            if (empty($_POST['name'])) {
                echo $lng['error_empty_title'] . "<br/><a href='index.php?act=write&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
                require_once('../incfiles/end.php');
                exit;
            }
            if (empty($_POST['text'])) {
                echo $lng['error_empty_text'] . "<br/><a href='index.php?act=write&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
                require_once('../incfiles/end.php');
                exit;
            }
            $text = trim($_POST['text']);
            if (!empty($_POST['anons'])) {
                $anons = mb_substr(trim($_POST['anons']), 0, 100);
            } else {
                $anons = mb_substr($text, 0, 100);
            }
            if ($rights == 5 || $rights >= 6) {
                $md = 1;
            } else {
                $md = 0;
            }
            mysql_query("INSERT INTO `lib` SET
                `refid` = '$id',
                `time` = '" . time() . "',
                `type` = 'bk',
                `name` = '" . mysql_real_escape_string(mb_substr(trim($_POST['name']), 0, 100)) . "',
                `announce` = '" . mysql_real_escape_string($anons) . "',
                `text` = '" . mysql_real_escape_string($text) . "',
                `avtor` = '$login',
                `ip` = '$ip',
                `soft` = '" . mysql_real_escape_string($agn) . "',
                `moder` = '$md'
            ");
            $cid = mysql_insert_id();
            if ($md == 1) {
                echo '<p>' . $lng_lib['article_added'] . '</p>';
            } else {
                echo '<p>' . $lng_lib['article_added'] . '<br/>' . $lng_lib['article_added_thanks'] . '</p>';
            }
            mysql_query("UPDATE `users` SET `lastpost` = '" . time() . "' WHERE `id` = '" . $user_id . "'");
            echo '<p><a href="index.php?id=' . $cid . '">' . $lng_lib['to_article'] . '</a></p>';
        } else {
            echo '<h3>' . $lng_lib['write_article'] . '</h3><form action="index.php?act=write&amp;id=' . $id . '" method="post">';
            echo '<p>' . $lng['title'] . ' (max. 100):<br/><input type="text" name="name"/></p>';
            echo '<p>' . $lng_lib['announce'] . ' (max. 100):<br/><input type="text" name="anons"/></p>';
            echo '<p>' . $lng['text'] . ':<br/><textarea name="text" rows="' . $set_user['field_h'] . '"></textarea></p>';
            echo '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>';
            echo '</form><p><a href ="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
        }
    } else {
        header("location: index.php");
    }
}
echo "<a href='index.php?'>" . $lng_lib['to_library'] . "</a><br/>";
?>