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
    if (empty ($_POST['cat'])) {
        $loaddir = $loadroot;
    }
    else {
        $cat = intval(trim($_POST['cat']));
        provcat($cat);
        $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
        $adrdir = mysql_fetch_array($cat1);
        $loaddir = "$adrdir[adres]/$adrdir[name]";
    }
    $opis = functions::check($_POST['opis']);
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
        }
        else {
            $newname = functions::check($_POST['newname']);
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
            echo "Вес файла превышает " . $set['flsz'] . " кб<br/>
<a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
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
        if (preg_match("/[^\dA-Za-z_\-.]+/", $newname)) {
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
            @ chmod("$ch", 0777);
            @ chmod("$loaddir/$ch", 0777);
            echo "Файл загружен!<br/>";
            mysql_query("insert into `download` values(0,'" . $cat . "','" . $loaddir . "','" . time() . "','" . $ch . "','file','','','','" . $opis . "','" . (isset($ch1) ? $ch1 : '') . "');");
        }
        else {
            echo "Ошибка при загрузке файла<br/>";
        }
    }
    if (!empty ($_POST['fail1'])) {
        $uploadedfile = $_POST['fail1'];
        if (strlen($uploadedfile) > 0) {
            $array = explode('file=', $uploadedfile);
            $tmp_name = $array [0];
            $filebase64 = $array [1];
        }
        $ftip = functions::format($tmp_name);
        if (empty ($_POST['newname'])) {
            $newname = str_replace(".$ftip", "", $tmp_name);
        }
        else {
            $newname = functions::check($_POST['newname']);
        }
        if (!empty ($_POST['screens1'])) {
            $uploaddir1 = "$screenroot";
            $uploadedfile1 = $_POST['screens1'];
            if (strlen($uploadedfile1) > 0) {
                $array1 = explode('file=', $uploadedfile1);
                $tmp_name1 = $array1[0];
                $filebas64 = $array1[1];
            }
            if (eregi("[^a-z0-9.()+_-]", $tmp_name1)) {
                echo
                "В названии файла <b>$tmp_name1</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat="
                . $cat . "'>Повторить</a></div>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $ffot = strtolower($tmp_name1);
            $dopras = array("gif", "jpg", "png");

            $formfot = functions::format($ffot);
            if (!in_array($formfot, $dopras)) {
                echo "Ошибка при загрузке скриншота.<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (strlen($filebas64) > 0) {
                $FileName1 = "$uploaddir/$newname.$ftip.$formfot";
                $filedata1 = base64_decode($filebas64);
                $fid1 = @ fopen($FileName1, "wb");
                if ($fid1) {
                    if (flock($fid1, LOCK_EX)) {
                        fwrite($fid1, $filedata1);
                        flock($fid1, LOCK_UN);
                    }
                    fclose($fid1);
                }
                if (file_exists($FileName1) && filesize($FileName1) == strlen($filedata1)) {
                    $sizsf = GetImageSize("$FileName1");
                    $widthf = $sizsf[0];
                    $heightf = $sizsf[1];
                    if ($widthf > 320 || $heightf > 320) {
                        echo "Размер картинки не должен превышать разрешения 320*320 px<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                        unlink("$FileName1");
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    echo 'Скриншот загружен!<br/>';

                    $ch1 = "$newname.$ftip.$formfot";

                }
                else {
                    echo 'Ошибка при загрузке скриншота<br/>';
                }
            }
        }
        $uploaddir = "$loaddir";
        if (eregi("[^a-z0-9.()+_-]", $tmp_name)) {
            echo
            "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat="
            . $cat . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (eregi("[^a-z0-9.()+_-]", $newname)) {
            echo
            "В новом названии файла <b>$newname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=select&amp;cat="
            . $cat . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if ((preg_match("/php/i", $tmp_name)) or (preg_match("/.pl/i", $tmp_name)) or ($fname == ".htaccess") or (preg_match("/php/i", $newname)) or (preg_match("/.pl/i", $newname)) or ($newname == ".htaccess")) {
            echo "Попытка отправить файл запрещенного типа.<br/><a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (strlen($filebase64) > 0) {
            $FileName = "$uploaddir/$newname.$ftip";
            $filedata = base64_decode($filebase64);
            $fid = @ fopen($FileName, "wb");
            if ($fid) {
                if (flock($fid, LOCK_EX)) {
                    fwrite($fid, $filedata);
                    flock($fid, LOCK_UN);
                }
                fclose($fid);
            }
            if (file_exists($FileName) && filesize($FileName) == strlen($filedata)) {
                $siz = filesize("$FileName");
                $siz = round($siz / 1024, 2);
                if ($siz >= 1024 * $set['flsz']) {
                    echo "Вес файла превышает " . $set['flsz'] . " кб<br/>
<a href='?act=select&amp;cat=" . $cat . "'>Повторить</a><br/>";
                    unlink("$FileName");
                    require_once ('../incfiles/end.php');
                    exit;
                }
                echo 'Файл загружен!<br/>';

                $ch = "$newname.$ftip";
                mysql_query("insert into `download` values(0,'" . $cat . "','" . $loaddir . "','" . time() . "','" . $ch . "','file','','','','" . $opis . "','" . $ch1 . "');");
            }
            else {
                echo 'Ошибка при загрузке файла<br/>';
            }
        }
    }
}
else {
    echo "Нет доступа!";
}
echo "&#187;<a href='?cat=" . $cat . "'>В папку</a><br/>";

?>