<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Перемещение картинки в другой альбом
if ($img && $user['id'] == $systemUser->id || $systemUser->rights >= 6) {
    $req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` = " . $user['id']);
    if ($req->rowCount()) {
        $image = $req->fetch();
        echo '<div class="phdr"><a href="?act=show&amp;al=' . $image['album_id'] . '&amp;user=' . $user['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Move image') . '</div>';
        if (isset($_POST['submit'])) {
            $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = " . $user['id']);

            if ($req_a->rowCount()) {
                $res_a = $req_a->fetch();
                $db->exec("UPDATE `cms_album_files` SET
                    `album_id` = '$al',
                    `access` = '" . $res_a['access'] . "'
                    WHERE `id` = '$img'
                ");
                echo '<div class="gmenu"><p>' . _t('Image successfully moved to the selected album') . '<br>' .
                    '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a></p></div>';
            } else {
                echo $tools->displayError(_t('Wrong data'));
            }
        } else {
            $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' AND `id` != '" . $image['album_id'] . "' ORDER BY `sort` ASC");

            if ($req->rowCount()) {
                echo '<form action="?act=image_move&amp;img=' . $img . '&amp;user=' . $user['id'] . '" method="post">' .
                    '<div class="menu"><p><h3>' . _t('Select Album') . '</h3>' .
                    '<select name="al">';

                while ($res = $req->fetch()) {
                    echo '<option value="' . $res['id'] . '">' . $tools->checkout($res['name']) . '</option>';
                }

                echo '</select></p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Move') . '"/></p>' .
                    '</div></form>' .
                    '<div class="phdr"><a href="?act=show&amp;al=' . $image['album_id'] . '&amp;user=' . $user['id'] . '">' . _t('Cancel') . '</a></div>';
            } else {
                echo $tools->displayError(_t('You must create at least one additional album in order to move the image'), '<a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a>');
            }
        }
    } else {
        echo $tools->displayError(_t('Wrong data'));
    }
}
