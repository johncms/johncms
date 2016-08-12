<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$mas = $db->query("select * from `download` where `id` = '$id'")->fetch();

if (!empty ($mas['name'])) {
    if (file_exists($mas['adres'] . '/' . $mas['name'])) {
        $sc = $mas['ip'] + 1;
        $db->exec("update `download` set `ip` = '$sc' where `id` = '$id'");
        $_SESSION['upl'] = '';
        header('location: ' . $mas['adres'] . '/' . $mas['name']);
    }
}

else {
    require_once ('../incfiles/head.php');
    echo "ERROR<br/>&#187;<a href='index.php'>Back</a><br/>";
}
