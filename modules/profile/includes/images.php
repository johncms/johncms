<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Verot\Upload\Upload;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$textl = _t('Edit Profile');

if (($user->id != $foundUser['id'] && $user->rights < 7) || $foundUser['rights'] > $user->rights) {
    // Если не хватает прав, выводим ошибку
    echo $tools->displayError(_t('You cannot edit profile of higher administration'));
    require 'system/end.php';
    exit;
}

switch ($mod) {
    case 'avatar':
        // Выгружаем аватар
        echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Upload Avatar') . '</div>';
        if (isset($_POST['submit'])) {
            $handle = new Upload($_FILES['imagefile']);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $foundUser['id'];
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
                        '<a href="?act=edit&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></p></div>';
                } else {
                    echo $tools->displayError($handle->error);
                }
                $handle->clean();
            }
        } else {
            echo '<form enctype="multipart/form-data" method="post" action="?act=images&amp;mod=avatar&amp;user=' . $foundUser['id'] . '">'
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
        echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Upload Photo') . '</div>';
        if (isset($_POST['submit'])) {
            $handle = new Upload($_FILES['imagefile']);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $foundUser['id'];
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
                    $handle->file_new_name_body = $foundUser['id'] . '_small';
                    $handle->file_overwrite = true;
                    $handle->image_resize = true;
                    $handle->image_x = 100;
                    $handle->image_ratio_y = true;
                    $handle->image_convert = 'jpg';
                    $handle->process('../files/users/photo/');
                    if ($handle->processed) {
                        echo '<div class="gmenu"><p>' . _t('The photo is successfully uploaded') . '<br /><a href="?act=edit&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></p></div>';
                    } else {
                        echo $tools->displayError($handle->error);
                    }
                } else {
                    echo $tools->displayError($handle->error);
                }
                $handle->clean();
            }
        } else {
            echo '<form enctype="multipart/form-data" method="post" action="?act=images&amp;mod=up_photo&amp;user=' . $foundUser['id'] . '"><div class="menu"><p>' . _t('Select image') . ':<br />' .
                '<input type="file" name="imagefile" value="" />' .
                '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $config['flsz']) . '" /></p>' .
                '<p><input type="submit" name="submit" value="' . _t('Upload') . '" /></p>' .
                '</div></form>' .
                '<div class="phdr"><small>' . sprintf(
                    _t('Allowed image formats: JPG, PNG, GIF. File size should not exceed %d kb.<br>The new image will replace old (if was).'),
                    $config['flsz']
                ) . '</small></div>';
        }
        break;
}
