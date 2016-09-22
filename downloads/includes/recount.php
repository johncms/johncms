<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 4 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $req_down = $db->query("SELECT `dir`, `name`, `id` FROM `download__category`");

    while ($res_down = $req_down->fetch()) {
        $dir_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `dir` LIKE '" . ($res_down['dir']) . "%'")->fetchColumn();
        $db->exec("UPDATE `download__category` SET `total` = '$dir_files' WHERE `id` = '" . $res_down['id'] . "'");
    }
}

header('Location: ?id=' . $id);
