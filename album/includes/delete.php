<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../system/head.php');

// Удалить альбом
if ($al && $user['id'] == $user_id || $rights >= 6) {
    /** @var Interop\Container\ContainerInterface $container */
    $container = App::getContainer();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Tools $tools */
    $tools = $container->get('tools');

    $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = '" . $user['id'] . "' LIMIT 1");
    if ($req_a->rowCount()) {
        $res_a = $req_a->fetch();
        echo '<div class="phdr"><a href="?act=list&amp;user=' . $user['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Delete') . '</div>';

        if (isset($_POST['submit'])) {
            $req = $db->query("SELECT * FROM `cms_album_files` WHERE `album_id` = " . $res_a['id']);

            while ($res = $req->fetch()) {
                // Удаляем файлы фотографий
                @unlink('../files/users/album/' . $user['id'] . '/' . $res['img_name']);
                @unlink('../files/users/album/' . $user['id'] . '/' . $res['tmb_name']);
                // Удаляем записи из таблицы голосований
                $db->exec("DELETE FROM `cms_album_votes` WHERE `file_id` = " . $res['id']);
                // Удаляем комментарии
                $db->exec("DELETE FROM `cms_album_comments` WHERE `sub_id` = " . $res['id']);
            }

            // Удаляем записи из таблиц
            $db->exec("DELETE FROM `cms_album_files` WHERE `album_id` = " . $res_a['id']);
            $db->exec("DELETE FROM `cms_album_cat` WHERE `id` = " . $res_a['id']);

            echo '<div class="menu"><p>' . _t('Album deleted') . '<br>' .
                '<a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a></p></div>';
        } else {
            echo '<div class="rmenu"><form action="?act=delete&amp;al=' . $al . '&amp;user=' . $user['id'] . '" method="post">' .
                '<p>' . _t('Album') . ': <b>' . $tools->checkout($res_a['name']) . '</b></p>' .
                '<p>' . _t('Are you sure you want to delete this album? If it contains photos, they also will be deleted.') . '</p>' .
                '<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p>' .
                '</form></div>' .
                '<div class="phdr"><a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        echo functions::display_error(_t('Wrong data'));
    }
}
