<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (($rights != 3 && $rights < 6) || !$id) {
    header('Location: http://johncms.com?act=404');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND (`type` = 't' OR `type` = 'm')");

if ($req->rowCount()) {
    $res = $req->fetch();
    $db->exec("UPDATE `forum` SET `close` = '0', `close_who` = '$login' WHERE `id` = '$id'");

    if ($res['type'] == 't') {
        header('Location: index.php?id=' . $id);
    } else {
        $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '" . $id . "'")->fetchColumn() / $kmess);
        header('Location: index.php?id=' . $res['refid'] . '&page=' . $page);
    }
} else {
    header('Location: index.php');
}
