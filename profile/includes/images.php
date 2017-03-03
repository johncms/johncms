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

$textl = _t('Edit Profile');
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (($systemUser->id != $user['id'] && $systemUser->rights < 7)
    || $user['rights'] > $systemUser->rights
) {
    // Если не хватает прав, выводим ошибку
    echo $tools->displayError(_t('You cannot edit profile of higher administration'));
    require('../system/end.php');
    exit;
}

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

switch ($mod) {
    case 'avatar':
        // Выгружаем аватар
        echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Upload Avatar') . '</div>';
        if (isset($_POST['submit'])) {
            $handle = new upload($_FILES['imagefile']);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $user['id'];
                //$handle->mime_check = false;
                $handle->allowed = [
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                ];
                $handle->file_max_size = 1024 * $config['flsz'];
                $handle->file_overwrite = true;
                $handle->image_resize = true;
                $handle->image_x = 32;
                $handle->image_y = 32;
                $handle->image_convert = 'png';
                $handle->process('../files/users/avatar/');
                if ($handle->processed) {
                    echo '<div class="gmenu"><p>' . _t('The avatar is successfully uploaded') . '<br />' .
                        '<a href="?act=edit&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a></p></div>';
                } else {
                    echo $tools->displayError($handle->error);
                }
                $handle->clean();
            }
        } else {
            echo '<form enctype="multipart/form-data" method="post" action="?act=images&amp;mod=avatar&amp;user=' . $user['id'] . '">'
                . '<div class="menu"><p>' . _t('Select Image') . ':<br />'
                . '<input type="file" name="imagefile" value="" />'
                . '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $config['flsz']) . '" /></p>'
                . '<p><input type="submit" name="submit" value="' . _t('Upload') . '" />'
                . '</p></div></form>'
                . '<div class="phdr"><small>'
                . sprintf(_t('Allowed image formats: JPG, PNG, GIF. File size should not exceed %d kb.<br>The new image will replace old (if was).'), $config['flsz'])
                . '</small></div>';
        }
        break;

    case 'up_photo':
        echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Upload Photo') . '</div>';
        if (isset($_POST['submit'])) {
            $handle = new upload($_FILES['imagefile']);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $user['id'];
                //$handle->mime_check = false;
                $handle->allowed = [
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                ];
                $handle->file_max_size = 1024 * $config['flsz'];
                $handle->file_overwrite = true;
                $handle->image_resize = true;
                $handle->image_x = 320;
                $handle->image_y = 240;
                $handle->image_ratio_no_zoom_in = true;
                //$handle->image_ratio_y = true;
                $handle->image_convert = 'jpg';
                $handle->process('../files/users/photo/');
                if ($handle->processed) {
                    // Обрабатываем превьюшку
                    $handle->file_new_name_body = $user['id'] . '_small';
                    $handle->file_overwrite = true;
                    $handle->image_resize = true;
                    $handle->image_x = 100;
                    $handle->image_ratio_y = true;
                    $handle->image_convert = 'jpg';
                    $handle->process('../files/users/photo/');
                    if ($handle->processed) {
                        echo '<div class="gmenu"><p>' . _t('The photo is successfully uploaded') . '<br /><a href="?act=edit&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a></p></div>';
                    } else {
                        echo $tools->displayError($handle->error);
                    }
                } else {
                    echo $tools->displayError($handle->error);
                }
                $handle->clean();
            }
        } else {
            echo '<form enctype="multipart/form-data" method="post" action="?act=images&amp;mod=up_photo&amp;user=' . $user['id'] . '"><div class="menu"><p>' . _t('Select image') . ':<br />' .
                '<input type="file" name="imagefile" value="" />' .
                '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $config['flsz']) . '" /></p>' .
                '<p><input type="submit" name="submit" value="' . _t('Upload') . '" /></p>' .
                '</div></form>' .
                '<div class="phdr"><small>' . sprintf(_t('Allowed image formats: JPG, PNG, GIF. File size should not exceed %d kb.<br>The new image will replace old (if was).'), $config['flsz']) . '</small></div>';
        }
        break;
}
