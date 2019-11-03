<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Удалить картинку
if ($img && $foundUser['id'] == $user->id || $user->rights >= 6) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}' AND `user_id` = '" . $foundUser['id'] . "' LIMIT 1");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $album = $res['album_id'];
        echo '<div class="phdr"><a href="?act=show&amp;al=' . $album . '&amp;user=' . $foundUser['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Delete image') . '</div>';
        //TODO: Администрация не должна удалять фотки старших по должности
        if (isset($_POST['submit'])) {
            // Удаляем файлы картинок
            @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name']);
            @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name']);

            // Удаляем записи из таблиц
            $db->exec("DELETE FROM `cms_album_files` WHERE `id` = '${img}'");
            $db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = '${img}'");
            $db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = '${img}'");

            header('Location: ?act=show&al=' . $album . '&user=' . $foundUser['id']);
        } else {
            echo '<div class="rmenu"><form action="?act=image_delete&amp;img=' . $img . '&amp;user=' . $foundUser['id'] . '" method="post">' .
                '<p>' . _t('Are you sure you want to delete this image?') . '</p>' .
                '<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p>' .
                '</form></div>' .
                '<div class="phdr"><a href="?act=show&amp;al=' . $album . 'user=' . $foundUser['id'] . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        echo $tools->displayError(_t('Wrong data'));
    }
}
