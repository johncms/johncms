<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\User $systemUser */
$systemUser = $container->get(Johncms\User::class);

if (($systemUser->rights != 3 && $systemUser->rights < 6) || !$id) {
    header('Location: index.php');
    exit;
}

if ($db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$id' AND `type` = 't'")->fetchColumn()) {
    if (isset($_GET['closed'])) {
        $db->exec("UPDATE `forum` SET `edit` = '1' WHERE `id` = '$id'");
    } else {
        $db->exec("UPDATE `forum` SET `edit` = '0' WHERE `id` = '$id'");
    }
}

header("Location: index.php?id=$id");
