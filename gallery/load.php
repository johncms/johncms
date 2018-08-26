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

if (!$user_id || isset($ban['1']) || isset($ban['14'])) {
    header("location: index.php"); exit;
}
if (!$id) {
    echo "ERROR<br/><a href='index.php'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}
// Проверка на флуд
$flood = functions::antiflood();
if ($flood) {
    require_once('../incfiles/head.php');
    echo functions::display_error('You cannot add pictures so often<br />Please wait ' . $flood . ' sec.', '<a href="index.php?id=' . $id . '">' . $lng['back'] . '</a>');
    require_once('../incfiles/end.php');
    exit;
}

$stmt = $db->query("select * from `gallery` where id='" . $id . "' AND `type` = 'al' LIMIT 1;");
if ($stmt->rowCount()) {
    echo "ERROR<br/><a href='index.php'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}
$ms = $stmt->fetch();
$rz1 = $db->query("select * from `gallery` where type='rz' and id='" . $ms['refid'] . "' LIMIT 1;")->fetch();
if ($rights >= 6) {
    $text = isset($_POST['text']) ? functions::checkin($_POST['text']) : '';
    $dopras = array (
        "gif",
        "jpg",
        "png"
    );
    $tff = implode(" ,", $dopras);
    $ftsz = $set['flsz'] / 5;
    $fname = $_FILES['fail']['name'];
    $fsize = $_FILES['fail']['size'];
    if ($fname != "") {
        $ffail = strtolower($fname);
        $formfail = functions::format($ffail);
        if ((preg_match("/php/i", $ffail)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess")) {
            echo "Trying to send a file type of prohibited.<br/><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
            require_once('../incfiles/end.php');
            exit;
        }
        if ($fsize >= 1024 * $ftsz) {
            echo "Weight file exceeds $ftsz kB<br/><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
            require_once('../incfiles/end.php');
            exit;
        }
        if (!in_array($formfail, $dopras)) {
            echo "Allowed only the following file types: $tff !.<br/><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
            require_once('../incfiles/end.php');
            exit;
        }
        if (preg_match("/[^\da-z_\-.]+/", $fname)) {
            echo "The image name contains invalid characters<br/><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br/>";
            require_once('../incfiles/end.php');
            exit;
        }
        if (file_exists("foto/$fname")) {
            $fname = time() . $fname;
        }
        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "foto/$fname")) == true) {
            $ch = $fname;
            @chmod("$ch", 0777);
            @chmod("foto/$ch", 0777);
            echo "Фото загружено!<br/><a href='index.php?id=" . $id . "'>" . $lng_gal['to_album'] . "</a><br/>";
            $stmt = $db->prepare("INSERT INTO `gallery` SET 
                `refid` = '" . $id . "',
                `time`  = '" . time() . "',
                `type`  = 'ft',
                `avtor` = ?,
                `text`  = ?,
                `name`  = '" . $ch . "'
            ");
            $stmt->execute([
                $login,
                $text
            ]);
            $db->exec("UPDATE `users` SET `lastpost` = '" . time() . "' WHERE `id` = '" . $user_id . "'");
        } else {
            echo $lng_gal['error_uploading_photo'] . "<br/><a href='index.php?id=" . $id . "'>" . $lng_gal['to_album'] . "</a><br/>";
        }
    }
} else {
    header("location: index.php"); exit;
}