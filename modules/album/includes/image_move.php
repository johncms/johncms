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
 * @var PDO $db
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

// Перемещение картинки в другой альбом
if ($img && $foundUser['id'] == $user->id || $user->rights >= 6) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '${img}' AND `user_id` = " . $foundUser['id']);
    if ($req->rowCount()) {
        $image = $req->fetch();
        echo '<div class="phdr"><a href="?act=show&amp;al=' . $image['album_id'] . '&amp;user=' . $foundUser['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Move image') . '</div>';
        if (isset($_POST['submit'])) {
            $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = " . $foundUser['id']);

            if ($req_a->rowCount()) {
                $res_a = $req_a->fetch();
                $db->exec(
                    "UPDATE `cms_album_files` SET
                    `album_id` = '${al}',
                    `access` = '" . $res_a['access'] . "'
                    WHERE `id` = '${img}'
                "
                );
                echo '<div class="gmenu"><p>' . _t('Image successfully moved to the selected album') . '<br>' .
                    '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></p></div>';
            } else {
                echo $tools->displayError(_t('Wrong data'));
            }
        } else {
            $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $foundUser['id'] . "' AND `id` != '" . $image['album_id'] . "' ORDER BY `sort` ASC");

            if ($req->rowCount()) {
                echo '<form action="?act=image_move&amp;img=' . $img . '&amp;user=' . $foundUser['id'] . '" method="post">' .
                    '<div class="menu"><p><h3>' . _t('Select Album') . '</h3>' .
                    '<select name="al">';

                while ($res = $req->fetch()) {
                    echo '<option value="' . $res['id'] . '">' . $tools->checkout($res['name']) . '</option>';
                }

                echo '</select></p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Move') . '"/></p>' .
                    '</div></form>' .
                    '<div class="phdr"><a href="?act=show&amp;al=' . $image['album_id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Cancel') . '</a></div>';
            } else {
                echo $tools->displayError(
                    _t('You must create at least one additional album in order to move the image'),
                    '<a href="?act=list&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a>'
                );
            }
        }
    } else {
        echo $tools->displayError(_t('Wrong data'));
    }
}
