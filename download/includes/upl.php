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

require_once ("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    if (!$cat) {
        $loaddir = $loadroot;
    } else {
        $error = false;
        $stmt = $db->query('SELECT * FROM `download` WHERE `type` = "cat" AND `id` = "' . $cat. '" LIMIT 1');
        if ($stmt->rowCount) {
            $res = $stmt->fetch();
            if (!is_dir($res['adres'] . '/' . $res['name'])) {
                $error = true;
            } else {
                $loaddir = $res['adres'] . '/' . $res['name'];
            }
        } else {
            $error = true;
        }
    }
    if (!$error) {
        $opis = functions::checkin($_POST['opis']);
        $fname = $_FILES['fail']['name'];
        $fsize = $_FILES['fail']['size'];
        $scrname = $_FILES['screens']['name'];
        $scrsize = $_FILES['screens']['size'];
        $scsize = @GetImageSize($_FILES['screens']['tmp_name']);
        $scwidth = $scsize[0];
        $scheight = $scsize[1];
        $ftip = functions::format($fname);
        $ffot = strtolower($scrname);
        $formfot = functions::format($ffot);
        $dopras = array("gif", "jpg", "png");
        if ($fname != "") {
            if (empty ($_POST['newname'])) {
                $newname = str_replace(".$ftip", "", $fname);
            } else {
                $newname = functions::checkin($_POST['newname']);
            }
            if ($scrname != "") {
                if (!in_array($formfot, $dopras)) {
                    echo "Ошибка при загрузке скриншота.<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
            }
            if ($scwidth > 320 || $scheight > 320) {
                echo "Размер картинки не должен превышать разрешения 320*320 px<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (preg_match("/[^\da-z_\-.]+/", $scrname)) {
                echo "В названии изображения $scrname присутствуют недопустимые символы<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if ($fsize >= 1024 * $set['flsz']) {
                echo "Вес файла превышает " . $set['flsz'] . " кб<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (preg_match("/[^\dA-Za-z_\-.]+/", $fname)) {
                echo
                "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat="
                . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (preg_match("/[^a-z0-9.()+_-]+/", $newname)) {
                echo
                "В новом названии файла <b>$newname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat="
                . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if ((preg_match("/.php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess") or (preg_match("/php/i", $newname)) or (preg_match("/.pl/i", $newname)) or ($newname == ".htaccess")) {
                echo "Попытка отправить файл запрещенного типа.<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $ch1 = "$newname.$ftip.$formfot";
            if ((move_uploaded_file($_FILES["screens"]["tmp_name"], "$screenroot/$newname.$ftip.$formfot")) == true) {
                @ chmod("$ch1", 0777);
                @ chmod("$screenroot/$ch1", 0777);
                echo "Скриншот загружен!<br/>";
            }
            $newname = "$newname.$ftip";
            if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "$loaddir/$newname")) == true) {
                $ch = $newname;
                @ chmod($ch, 0777);
                @ chmod("$loaddir/$ch", 0777);
                echo "Файл загружен!<br/>";
                $stmt = $db->prepare("INSERT INTO `download` SET
                    `refid` = $cat,
                    `adres` = '" . $loaddir . "',
                    `time` = " . time() . ",
                    `name` = '" . $ch . "',
                    `type` = 'file',
                    `ip` = '',
                    `soft` = '',
                    `text` = ?,
                    `screen` = '" . (isset($ch1) ? $ch1 : '') . "'
                ");
                $stmt->execute([
                    $opis
                ]);
            } else {
                echo "Ошибка при загрузке файла<br/>";
            }
        }
        echo "&#187;<a href='?cat=" . $cat . "'>В папку</a><br/>";
    } else {
        echo 'ERROR<br/><a href="?">Back</a><br/>';
    }
} else {
    echo 'ERROR<br/><a href="?">Back</a><br/>';
}