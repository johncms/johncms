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
    if ($id != 0 && $ms['type'] != "cat") {
        echo "";
        require_once('../incfiles/end.php');
        exit;
    }
    if ($ms['ip'] == 0) {
        if (isset($_POST['submit'])) {
            if (empty($_POST['name'])) {
                echo functions::display_error($lng['error_empty_title'], '<a href="index.php?act=load&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
                require_once('../incfiles/end.php');
                exit;
            }
            $name = mb_substr($_POST['name'], 0, 50);
            $fname = $_FILES['fail']['name'];
            $ftip = functions::format($fname);
            $ftip = strtolower($ftip);
            if ($fname != "") {
                if (eregi("[^a-z0-9.()+_-]", $fname)) {
                    echo "Invalid file name<br /><a href='index.php?act=load&amp;id="
                        . $id . "'>" . $lng['repeat'] . "</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/.php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess")) {
                    echo "Invalid file format<br/><a href='index.php?act=load&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                if ($ftip != "txt") {
                    echo "This is not a text file<br/><a href='index.php?act=load&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "temp/$fname")) == true) {
                    $ch = $fname;
                    @chmod("$ch", 0777);
                    @chmod("temp/$ch", 0777);
                    $txt = file_get_contents("temp/$ch");
                    if (mb_check_encoding($txt, 'UTF-8')) { } elseif (mb_check_encoding($txt, 'windows-1251')) {
                        $txt = iconv("windows-1251", "UTF-8", $txt);
                    }  elseif (mb_check_encoding($txt, 'KOI8-R')) {
                        $txt = iconv("KOI8-R", "UTF-8", $txt);
                    } else {
                        echo "File in an unknown encoding<br /><a href='index.php?act=load&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
                        require_once('../incfiles/end.php');
                        exit;
                    }
                    $anons = !empty($_POST['anons']) ? mb_substr($_POST['anons'], 0, 100) : mb_substr($txt, 0, 100);
                    mysql_query("insert into `lib` set
                        `refid`='" . $id . "',
                        `time`='" . time() . "',
                        `type`='bk',
                        `name`='" . mysql_real_escape_string($name) . "',
                        `announce`='" . mysql_real_escape_string($anons) . "',
                        `avtor`='" . $login . "',
                        `text`='" . mysql_real_escape_string($txt) . "',
                        `ip`='" . $ip . "',
                        `soft`='" . mysql_real_escape_string($agn) . "',
                        `moder`='1'
                    ");
                    unlink("temp/$ch");
                    $cid = mysql_insert_id();
                    echo $lng_lib['article_added'] . "<br/><a href='index.php?id=" . $cid . "'>" . $lng_lib['to_article'] . "</a><br/>";
                } else {
                    echo $lng_lib['error_uploading'] . "<br/><a href='index.php?act=load&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
            }
        } else {
            echo '<h3>' . $lng_lib['upload_article'] . '</h3>' . $lng_lib['supported_encoding'] . ' Win-1251, KOI8-R, UTF-8<br/><br/>' .
                '<form action="index.php?act=load&amp;id=' . $id . '" method="post" enctype="multipart/form-data">' .
                $lng['title'] . ' (max. 50)<br/>' . '<input type="text" name="name"/><br/>' .
                $lng_lib['announce'] . ' (max. 100)<br/><input type="text" name="anons"/><br/>' .
                $lng_lib['select_text_file'] . ' ( .txt):<br/><input type="file" name="fail"/>' .
                '<p><input type="submit" name="submit" value="' . $lng['sent'] . '"/></p>' .
                '</form>' .
                '<p><a href ="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
        }
    }
} else {
    header("location: index.php");
}
?>