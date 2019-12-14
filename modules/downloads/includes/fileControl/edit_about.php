<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO                       $db
 * @var Johncms\System\Users\User $user
 */

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name']) || ($user->rights < 6 && $user->rights != 4)) {
    echo '<a href="?">' . _t('Downloads') . '</a>';
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

if (isset($_POST['submit'])) {
    $text = isset($_POST['opis']) ? trim($_POST['opis']) : '';

    $stmt = $db->prepare('
        UPDATE `download__files` SET
        `about`    = ?
        WHERE `id` = ?
    ');

    $stmt->execute([
        $text,
        $id,
    ]);

    header('Location: ?act=view&id=' . $id);
} else {
    echo '<div class="phdr"><b>' . _t('Description') . ':</b> ' . htmlspecialchars($res_down['rus_name']) . '</div>' .
        '<div class="list1"><form action="?act=edit_about&amp;id=' . $id . '" method="post"><p>' .
        '<small>' . _t('Maximum 500 characters') . '</small><br>' .
        '<textarea name="opis">' . htmlentities($res_down['about'], ENT_QUOTES, 'UTF-8') . '</textarea><br>' .
        '<input type="submit" name="submit" value="' . _t('Save') . '"/></p></form></div>' .
        '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
}

echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
