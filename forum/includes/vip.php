<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    if (empty($_GET['id'])) {
        require('../system/head.php');
        echo functions::display_error(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    if ($db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '" . $id . "' AND `type` = 't'")->fetchColumn()) {
        $db->exec("UPDATE `forum` SET  `vip` = '" . (isset($_GET['vip']) ? '1' : '0') . "' WHERE `id` = '$id'");
        header('Location: index.php?id=' . $id);
    } else {
        require('../system/head.php');
        echo functions::display_error(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }
}
