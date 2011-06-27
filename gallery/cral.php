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

if ($rights >= 6) {
    if (empty($_GET['id'])) {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    $type = mysql_query("select * from `gallery` where id='" . $id . "';");
    $ms = mysql_fetch_array($type);
    if ($ms['type'] != "rz") {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $text = functions::check($_POST['text']);
        mysql_query("insert into `gallery` values(0,'" . $id . "','" . time() . "','al','','" . $text . "','','','','');");
        header("location: index.php?id=$id");
    } else {
        echo $lng_gal['create_album'] . "<br/><form action='index.php?act=cral&amp;id=" . $id .
            "' method='post'>" . $lng['title'] . ":<br/><input type='text' name='text'/><br/><input type='submit' name='submit' value='" . $lng['save'] . "'/></form><br/><a href='index.php?id=" . $id . "'>" . $lng_gal['to_section'] . "</a><br/>";
    }
} else {
    header("location: index.php");
}

?>