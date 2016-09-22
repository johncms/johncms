<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($res_down['type'] == 3 && $rights < 6 && $rights != 4)) {
    $error = true;
} else {
    $link = $res_down['dir'] . '/' . $res_down['name'];
}

$more = isset($_GET['more']) ? abs(intval($_GET['more'])) : false;

if ($more) {
    $req_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = '" . $id . "' AND `id` = '$more' LIMIT 1");
    $res_more = $req_more->fetch();

    if (!$req_more->rowCount() || !is_file($res_down['dir'] . '/' . $res_more['name'])) {
        $error = true;
    } else {
        $link = $res_down['dir'] . '/' . $res_more['name'];
    }
}

if ($error) {
    header('Location: ' . App::cfg()->sys->homeurl . '404');
} else {
    if (!isset($_SESSION['down_' . $id])) {
        $db->exec("UPDATE `download__files` SET `field`=`field`+1 WHERE `id`=" . $id);
        $_SESSION['down_' . $id] = 1;
    }

    header('Location: ' . $link);
}
