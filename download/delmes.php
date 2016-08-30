<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 4 || $rights >= 6) {
    if (empty($_GET['id'])) {
        require_once("../incfiles/head.php");
        echo "ERROR<br><a href='index.php?'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $id = intval($_GET['id']);
    $ms = $db->query("SELECT * FROM `download` WHERE id='" . $id . "'")->fetch();

    if ($ms['type'] != 'komm') {
        require_once("../incfiles/head.php");
        echo "ERROR<br><a href='index.php?'>Back</a><br>";
        require_once('../incfiles/end.php');
        exit;
    }

    $db->exec("DELETE FROM `download` WHERE `id`='" . $id . "'");
    header("location: index.php?act=komm&id=$ms[refid]");
}
