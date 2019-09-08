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

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

// Выгрузка фотографии
if ($al && $user['id'] == $systemUser->id && empty($systemUser->ban) || $systemUser->rights >= 7) {
    $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = " . $user['id']);

    if (!$req_a->rowCount()) {
        // Если альбома не существует, завершаем скрипт
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    $res_a = $req_a->fetch();
    echo '<div class="phdr"><a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Upload image') . '</div>';

    if (isset($_POST['submit'])) {
        $handle = new upload($_FILES['imagefile']);

        if ($handle->uploaded) {
            // Обрабатываем фото
            $handle->file_new_name_body = 'img_' . time();
            $handle->allowed = [
                'image/jpeg',
                'image/gif',
                'image/png',
            ];
            $handle->file_max_size = 1024 * $config['flsz'];
            $handle->image_resize = true;
            $handle->image_x = 1920;
            $handle->image_y = 1024;
            $handle->image_ratio_no_zoom_in = true;
            $handle->image_convert = 'jpg';
            // Поставить в зависимость от настроек в Админке
            //$handle->image_text = $set['homeurl'];
            //$handle->image_text_x = 0;
            //$handle->image_text_y = 0;
            //$handle->image_text_font = 3;
            //$handle->image_text_background = '#AAAAAA';
            //$handle->image_text_background_percent = 50;
            //$handle->image_text_padding = 1;
            $handle->process('../files/users/album/' . $user['id'] . '/');
            $img_name = $handle->file_dst_name;

            if ($handle->processed) {
                // Обрабатываем превьюшку
                $handle->file_new_name_body = 'tmb_' . time();
                $handle->image_resize = true;
                $handle->image_x = 100;
                $handle->image_y = 100;
                $handle->image_ratio_no_zoom_in = true;
                $handle->image_convert = 'jpg';
                $handle->process('../files/users/album/' . $user['id'] . '/');
                $tmb_name = $handle->file_dst_name;

                if ($handle->processed) {
                    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                    $description = mb_substr($description, 0, 500);

                    $db->prepare('
                      INSERT INTO `cms_album_files` SET
                      `album_id` = ?,
                      `user_id` = ?,
                      `img_name` = ?,
                      `tmb_name` = ?,
                      `description` = ?,
                      `time` = ?,
                      `access` = ?
                    ')->execute([
                        $al,
                        $user['id'],
                        $img_name,
                        $tmb_name,
                        $description,
                        time(),
                        $res_a['access'],
                    ]);

                    echo '<div class="gmenu"><p>' . _t('Image uploaded') . '<br>' .
                        '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a></p></div>' .
                        '<div class="phdr"><a href="../profile/?user=' . $user['id'] . '">' . _t('Profile') . '</a></div>';
                } else {
                    echo $tools->displayError($handle->error);
                }
            } else {
                echo $tools->displayError($handle->error);
            }
            $handle->clean();
        } else {
            echo $tools->displayError(_t('File not selected'));
        }
    } else {
        echo '<form enctype="multipart/form-data" method="post" action="?act=image_upload&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' .
            '<div class="menu"><p><h3>' . _t('Image') . '</h3>' .
            '<input type="file" name="imagefile" value="" /></p>' .
            '<p><h3>' . _t('Description') . '</h3>' .
            '<textarea name="description" rows="' . $systemUser->getConfig()->fieldHeight . '"></textarea><br>' .
            '<small>' . _t('Optional field') . ', max. 500</small></p>' .
            '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $config['flsz']) . '" />' .
            '<p><input type="submit" name="submit" value="' . _t('Upload') . '" /></p>' .
            '</div></form>' .
            '<div class="phdr"><small>' . sprintf(_t('Allowed format image JPG, JPEG, PNG, GIF<br>File size should not exceed %d kb.'), $config['flsz']) . '</small></div>' .
            '<p><a href="?act=show&amp;al=' . $al . '&amp;user=' . $user['id'] . '">' . _t('Back') . '</a></p>';
    }
}
