<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$config = di('config')['johncms'];

// Выгрузка фотографии
if (($al && $foundUser['id'] == $user->id && empty($user->ban)) || $user->rights >= 7) {
    $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = " . $foundUser['id']);

    if (! $req_a->rowCount()) {
        // Если альбома не существует, завершаем скрипт
        echo $tools->displayError(_t('Wrong data'));
        echo $view->render(
            'system::app/old_content',
            [
                'title'   => $textl ?? '',
                'content' => ob_get_clean(),
            ]
        );
        exit;
    }

    $res_a = $req_a->fetch();
    echo '<div class="phdr"><a href="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '"><b>' . _t('Photo Album') . '</b></a> | ' . _t('Upload image') . '</div>';

    if (isset($_POST['submit'])) {
        $handle = new \Verot\Upload\Upload($_FILES['imagefile']);

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
            $handle->process(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/');
            $img_name = $handle->file_dst_name;

            if ($handle->processed) {
                // Обрабатываем превьюшку
                $handle->file_new_name_body = 'tmb_' . time();
                $handle->image_resize = true;
                $handle->image_x = 100;
                $handle->image_y = 100;
                $handle->image_ratio_no_zoom_in = true;
                $handle->image_convert = 'jpg';
                $handle->process(UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/');
                $tmb_name = $handle->file_dst_name;

                if ($handle->processed) {
                    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                    $description = mb_substr($description, 0, 500);

                    $db->prepare(
                        '
                      INSERT INTO `cms_album_files` SET
                      `album_id` = ?,
                      `user_id` = ?,
                      `img_name` = ?,
                      `tmb_name` = ?,
                      `description` = ?,
                      `time` = ?,
                      `access` = ?
                    '
                    )->execute(
                        [
                            $al,
                            $foundUser['id'],
                            $img_name,
                            $tmb_name,
                            $description,
                            time(),
                            $res_a['access'],
                        ]
                    );

                    echo '<div class="gmenu"><p>' . _t('Image uploaded') . '<br>' .
                        '<a href="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></p></div>' .
                        '<div class="phdr"><a href="../profile/?user=' . $foundUser['id'] . '">' . _t('Profile') . '</a></div>';
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
        echo '<form enctype="multipart/form-data" method="post" action="?act=image_upload&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '">' .
            '<div class="menu"><p><h3>' . _t('Image') . '</h3>' .
            '<input type="file" name="imagefile" value="" /></p>' .
            '<p><h3>' . _t('Description') . '</h3>' .
            '<textarea name="description" rows="' . $user->config->fieldHeight . '"></textarea><br>' .
            '<small>' . _t('Optional field') . ', max. 500</small></p>' .
            '<input type="hidden" name="MAX_FILE_SIZE" value="' . (1024 * $config['flsz']) . '" />' .
            '<p><input type="submit" name="submit" value="' . _t('Upload') . '" /></p>' .
            '</div></form>' .
            '<div class="phdr"><small>' . sprintf(
                _t('Allowed format image JPG, JPEG, PNG, GIF<br>File size should not exceed %d kb.'),
                $config['flsz']
            ) . '</small></div>' .
            '<p><a href="?act=show&amp;al=' . $al . '&amp;user=' . $foundUser['id'] . '">' . _t('Back') . '</a></p>';
    }
}
