<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (($rights != 3 && $rights < 6) || !$id) {
    header('Location: index.php');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

if ($db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$id' AND `type` = 't'")->fetchColumn()) {
    if (isset($_GET['closed'])) {
        $db->exec("UPDATE `forum` SET `edit` = '1' WHERE `id` = '$id'");
    } else {
        $db->exec("UPDATE `forum` SET `edit` = '0' WHERE `id` = '$id'");
    }
}

header("Location: index.php?id=$id");
