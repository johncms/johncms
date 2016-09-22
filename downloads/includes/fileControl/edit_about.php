<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Редактирование описания файла
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($rights < 6 && $rights != 4)) {
    echo '<a href="' . $url . '">' . $lng['download_title'] . '</a>';
    exit;
}

if (isset($_POST['submit'])) {
    $text = isset($_POST['opis']) ? trim($_POST['opis']) : '';

    $stmt = $db->prepare("
        UPDATE `download__files` SET
        `about`    = ?
        WHERE `id` = ?
    ");

    $stmt->execute([
        $text,
        $id,
    ]);

    header('Location: ' . $url . '?act=view&id=' . $id);
} else {
    echo '<div class="phdr"><b>' . $lng['dir_desc'] . ':</b> ' . htmlspecialchars($res_down['rus_name']) . '</div>' .
        '<div class="list1"><form action="' . $url . '?act=edit_about&amp;id=' . $id . '" method="post">' .
        '<small>' . $lng['desc_file_faq'] . '</small><br />' .
        '<textarea name="opis">' . htmlentities($res_down['about'], ENT_QUOTES, 'UTF-8') . '</textarea><br />' .
        '<input type="submit" name="submit" value="' . $lng['sent'] . '"/></form></div>' .
        '<div class="phdr"><a href="' . $url . '?act=view&amp;id=' . $id . '">' . $lng['back'] . '</a></div>';
}
