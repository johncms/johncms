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
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\System\Users\User  $user
 */

// Удалить альбом
if ($al && $foundUser['id'] == $user->id || $user->rights >= 6) {
    $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = '" . $foundUser['id'] . "' LIMIT 1");

    if ($req_a->rowCount()) {
        $res_a = $req_a->fetch();
        echo '<div class="phdr"><a href="?act=list&amp;user=' . $foundUser['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Delete') . '</div>';

        if (isset($_POST['submit'])) {
            $req = $db->query('SELECT * FROM `cms_album_files` WHERE `album_id` = ' . $res_a['id']);

            while ($res = $req->fetch()) {
                // Удаляем файлы фотографий
                @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['img_name']);
                @unlink(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/' . $res['tmb_name']);
                // Удаляем записи из таблицы голосований
                $db->exec('DELETE FROM `cms_album_votes` WHERE `file_id` = ' . $res['id']);
                // Удаляем комментарии
                $db->exec('DELETE FROM `cms_album_comments` WHERE `sub_id` = ' . $res['id']);
            }

            // Удаляем записи из таблиц
            $db->exec('DELETE FROM `cms_album_files` WHERE `album_id` = ' . $res_a['id']);
            $db->exec('DELETE FROM `cms_album_cat` WHERE `id` = ' . $res_a['id']);

            echo '<div class="menu"><p>' . _t('Album deleted') . '<br>' .
                '<a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></p></div>';
        } else {
            echo '<div class="rmenu"><form action="?act=delete&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '" method="post">' .
                '<p>' . _t('Album') . ': <b>' . $tools->checkout($res_a['name']) . '</b></p>' .
                '<p>' . _t('Are you sure you want to delete this album? If it contains photos, they also will be deleted.') . '</p>' .
                '<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p>' .
                '</form></div>' .
                '<div class="phdr"><a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        echo $tools->displayError(_t('Wrong data'));
    }
}
