<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (!$user_id || $ban['1'] || $ban['14']) {
    header("location: index.php");
    exit;
}

if (empty($_GET['id'])) {
    echo "ERROR<br><a href='index.php'>Back</a><br>";
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

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$ms = $db->query("SELECT * FROM `gallery` WHERE id = " . $id)->fetch();

if ($ms['type'] != "al") {
    echo "ERROR<br><a href='index.php'>Back</a><br>";
    require_once('../incfiles/end.php');
    exit;
}

$rz1 = $db->query("SELECT * FROM `gallery` WHERE type='rz' AND id='" . $ms['refid'] . "'")->fetch();

if ((!empty($_SESSION['uid']) && $rz1['user'] == 1 && $ms['text'] == $login) || $rights >= 6) {
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $dopras = [
        "gif",
        "jpg",
        "png",
    ];
    $tff = implode(" ,", $dopras);
    $ftsz = $set['flsz'] / 5;
    $fname = $_FILES['fail']['name'];
    $fsize = $_FILES['fail']['size'];

    if ($fname != "") {
        $ffail = strtolower($fname);
        $formfail = functions::format($ffail);

        if ((preg_match("/php/i", $ffail)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess")) {
            echo "Trying to send a file type of prohibited.<br><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br>";
            require_once('../incfiles/end.php');
            exit;
        }

        if ($fsize >= 1024 * $ftsz) {
            echo "Weight file exceeds $ftsz kB<br><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br>";
            require_once('../incfiles/end.php');
            exit;
        }

        if (!in_array($formfail, $dopras)) {
            echo "Allowed only the following file types: $tff !.<br><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br>";
            require_once('../incfiles/end.php');
            exit;
        }

        if (preg_match("/[^\da-z_\-.]+/", $fname)) {
            echo "The image name contains invalid characters<br><a href='index.php?act=upl&amp;id=" . $id . "'>" . $lng['repeat'] . "</a><br>";
            require_once('../incfiles/end.php');
            exit;
        }

        if ($rz1['user'] == 1 && $ms['text'] == $login) {
            $fname = "$_SESSION[pid].$fname";
        }

        if (file_exists("foto/$fname")) {
            $fname = time() . $fname;
        }

        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "foto/$fname")) == true) {
            $ch = $fname;
            @chmod("$ch", 0777);
            @chmod("foto/$ch", 0777);
            echo "Фото загружено!<br><a href='index.php?id=" . $id . "'>" . $lng_gal['to_album'] . "</a><br>";
            $db->exec("INSERT INTO `gallery` VALUES(0,'" . $id . "','" . time() . "','ft','" . $login . "'," . $db->quote($text) . ",'" . $ch . "','','','');");
            $db->exec("UPDATE `users` SET `lastpost` = '" . time() . "' WHERE `id` = '" . $user_id . "'");
        } else {
            echo $lng_gal['error_uploading_photo'] . "<br><a href='index.php?id=" . $id . "'>" . $lng_gal['to_album'] . "</a><br>";
        }
    }
} else {
    header("location: index.php");
}
