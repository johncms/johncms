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
if ($rights == 5 || $rights >= 6) {
    if ($_GET['id'] == "") {
        echo "";
        require_once('../incfiles/end.php');
        exit;
    }
    $typ = mysql_query("select * from `lib` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if ($id != 0 && ($ms['type'] == "bk" || $ms['type'] == "komm")) {
        echo "";
        require_once('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        if (empty($_POST['text'])) {
            echo functions::display_error($lng['error_empty_title'], '<a href="index.php?act=mkcat&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
            require_once('../incfiles/end.php');
            exit;
        }
        $text = functions::check($_POST['text']);
        $user = isset($_POST['user']);
        $typs = intval($_POST['typs']);
        mysql_query("INSERT INTO `lib` SET
            `refid` = '$id',
            `time` = '" . time() . "',
            `type` = 'cat',
            `text` = '$text',
            `ip` = '$typs',
            `soft` = '$user'
        ");
        $cid = mysql_insert_id();
        echo $lng_lib['category_created'] . "<br/><a href='index.php?id=" . $cid . "'>" . $lng_lib['to_category'] . "</a><br/>";
    } else {
        echo $lng_lib['create_category'] . '<br/>' .
             '<form action="index.php?act=mkcat&amp;id=' . $id . '" method="post">' .
             $lng['title'] . ':<br/>' .
             '<input type="text" name="text"/>' .
             '<p>' . $lng_lib['category_type'] . '<br/>' .
             '<select name="typs">' .
             '<option value="1">' . $lng_lib['categories'] . '</option>' .
             '<option value="0">' . $lng_lib['articles'] . '</option>' .
             '</select></p>' .
             '<p><input type="checkbox" name="user" value="1"/>' . $lng_lib['if_articles'] . '</p>' .
             '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
             '</form>' .
             '<p><a href ="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
    }
} else {
    header("location: index.php");
}
?>