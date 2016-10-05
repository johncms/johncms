<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

// Редактирование файла
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($rights < 6 && $rights != 4)) {
    echo '<a href="?">' . _t('Downloads') . '</a>';
    exit;
}

if (isset($_POST['submit'])) {
    $name = isset($_POST['text']) ? trim($_POST['text']) : null;
    $name_link = isset($_POST['name_link']) ? htmlspecialchars(mb_substr($_POST['name_link'], 0, 200)) : null;

    if ($name_link && $name) {
        $stmt = $db->prepare("
            UPDATE `download__files` SET
            `rus_name` = ?,
            `text`     = ?
            WHERE `id` = ?
        ");

        $stmt->execute([
            $name,
            $name_link,
            $id,
        ]);

        header('Location: ?act=view&id=' . $id);
    } else {
        echo _t('The required fields are not filled') . ' <a href="?act=edit_file&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
    }
} else {
    $file_name = htmlspecialchars($res_down['rus_name']);
    echo '<div class="phdr"><b>' . $file_name . '</b></div>' .
        '<div class="list1"><form action="?act=edit_file&amp;id=' . $id . '" method="post">' .
        _t('File Name') . '(мах. 200):<br><input type="text" name="text" value="' . $file_name . '"/><br>' .
        _t('Link to download file') . ' (мах. 200):<br><input type="text" name="name_link" value="' . $res_down['text'] . '"/><br>' .
        '<input type="submit" name="submit" value="' . _t('Save') . '"/></form></div>' .
        '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
}
