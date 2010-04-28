<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (!$user_id || $ban['1'] || $ban['14']) {
    header("location: index.php");
    exit;
}
if (empty ($_GET['id'])) {
    echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
// Проверка на флуд
$flood = antiflood();
if ($flood){
    require_once ('../incfiles/head.php');
    echo display_error('Вы не можете так часто добавлять картинки<br />Пожалуйста, подождите ' . $flood . ' сек.', '<a href="index.php?id=' . $id . '">Назад</a>');
    require_once ('../incfiles/end.php');
    exit;
}

$type = mysql_query("select * from `gallery` where id='" . $id . "';");
$ms = mysql_fetch_array($type);
if ($ms['type'] != "al") {
    echo "Ошибка!<br/><a href='index.php'>В галерею</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$rz = mysql_query("select * from `gallery` where type='rz' and id='" . $ms['refid'] . "';");
$rz1 = mysql_fetch_array($rz);
if ((!empty ($_SESSION['uid']) && $rz1['user'] == 1 && $ms['text'] == $login) || $rights >= 6) {
    $text = check($_POST['text']);
    $dopras = array("gif", "jpg", "png");
    $tff = implode(" ,", $dopras);
    $ftsz = $flsz / 5;
    $fname = $_FILES['fail']['name'];
    $fsize = $_FILES['fail']['size'];
    if ($fname != "") {
        $ffail = strtolower($fname);
        $formfail = format($ffail);
        if ((preg_match("/php/i", $ffail)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess")) {
            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if ($fsize >= 1024 * $ftsz) {
            echo "Вес файла превышает $ftsz кб<br/>
<a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (!in_array($formfail, $dopras)) {
            echo "Разрешены только следующие типы файлов: $tff !.<br/><a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (eregi("[^a-z0-9.()+_-]", $fname)) {
            echo "В названии изображения $fname присутствуют недопустимые символы<br/><a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if ($rz1['user'] == 1 && $ms['text'] == $login) {
            $fname = "$_SESSION[pid].$fname";
        }
        if (file_exists("foto/$fname")) {
            $fname = "$realtime.$fname";
        }
        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "foto/$fname")) == true) {
            $ch = $fname;
            @ chmod("$ch", 0777);
            @ chmod("foto/$ch", 0777);
            echo "Фото загружено!<br/><a href='index.php?id=" . $id . "'>В альбом</a><br/>";
            mysql_query("insert into `gallery` values(0,'" . $id . "','" . $realtime . "','ft','" . $login . "','" . $text . "','" . $ch . "','','','');");
            mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
        }
        else {
            echo "Ошибка при загрузке фото<br/><a href='index.php?id=" . $id . "'>В альбом</a><br/>";
        }
    }
    if (!empty ($_POST['fail1'])) {
        $uploadedfile = $_POST['fail1'];
        if (strlen($uploadedfile) > 0) {
            $array = explode('file=', $uploadedfile);
            $tmp_name = $array [0];
            $filebase64 = $array [1];
        }
        $ffail = strtolower($tmp_name);
        $fftip = format($ffail);
        if ((preg_match("/php/i", $ffail)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess")) {
            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (strlen(base64_decode($filebase64)) >= 1024 * $ftsz) {
            echo "Вес файла превышает $ftsz кб<br/>
<a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (!in_array($fftip, $dopras)) {
            echo "Разрешены только следующие типы файлов: $tff !.<br/><a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (eregi("[^a-z0-9.()+_-]", $tmp_name)) {
            echo "В названии изображения $tmp_name присутствуют недопустимые символы<br/><a href='index.php?act=upl&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        if (strlen($filebase64) > 0) {
            if ($rz1['user'] == 1 && $ms['text'] == $login) {
                $tmp_name = "$_SESSION[pid].$tmp_name";                ####7.02.08
            }
            if (file_exists("foto/$fname")) {
                $tmp_name = "$realtime.$tmp_name";
            }

            $FileName = "foto/$tmp_name";
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
                echo "Фото загружено!<br/><a href='index.php?id=" . $id . "'>В альбом</a><br/>";
                $ch = "$tmp_name";
                mysql_query("insert into `gallery` values(0,'" . $id . "','" . $realtime . "','ft','" . $login . "','" . $text . "','" . $ch . "','','','');");
                mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
            }
            else {
                echo "Ошибка при загрузке фото<br/><a href='index.php?id=" . $id . "'>В альбом</a><br/>";
            }
        }
    }
}
else {
    header("location: index.php");
}

?>