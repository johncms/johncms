<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($adm) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $stmt = $db->query("SELECT `id`, `pos` FROM `library_cats` WHERE " . ($do == 'dir' ? '`parent`=' . $id : '`parent`=0') . " ORDER BY `pos` ASC");
    $y = 0;
    $arrsort = [];

    if ($stmt->rowCount()) {
        while ($row = $stmt->fetch()) {
            $y++;
            $arrsort[$y] = $row['id'] . '|' . $row['pos'];
        }
    }

    $type = isset($_GET['moveset']) && in_array($_GET['moveset'], ['up', 'down']) ? $_GET['moveset'] : redir404();
    $posid = isset($_GET['posid']) && $_GET['posid'] > 0 ? intval($_GET['posid']) : redir404();
    list($num1, $pos1) = explode('|', $arrsort[$posid]);
    list($num2, $pos2) = explode('|', $arrsort[($type == 'up' ? $posid - 1 : $posid + 1)]);
    $db->exec('UPDATE `library_cats` SET `pos`=' . $pos2 . ' WHERE `id`=' . $num1);
    $db->exec('UPDATE `library_cats` SET `pos`=' . $pos1 . ' WHERE `id`=' . $num2);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
