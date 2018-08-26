<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Редактировать картинку
-----------------------------------------------------------------
*/
if ($img && $user['id'] == $user_id || $rights >= 6) {
    $stmt = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` = '" . $user['id'] . "'");
    if ($stmt->rowCount()) {
        $res = $stmt->fetch();
        $album = $res['album_id'];
        echo '<div class="phdr"><a href="album.php?act=show&amp;al=' . $album . '&amp;user=' . $user['id'] . '"><b>' . $lng['photo_album'] . '</b></a> | ' . $lng_profile['image_edit'] . '</div>';
        if (isset($_POST['submit'])) {
            if (!isset($_SESSION['post'])) {
                $_SESSION['post'] = true;
                $sql = '';
                $ph = [];
                $rotate = isset($_POST['rotate']) ? intval($_POST['rotate']) : 0;
                $brightness = isset($_POST['brightness']) ? intval($_POST['brightness']) : 0;
                $contrast = isset($_POST['contrast']) ? intval($_POST['contrast']) : 0;
                $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                $description = mb_substr($description, 0, 500);
                if ($rotate == 1 || $rotate == 2 || ($brightness > 0 && $brightness < 5) || ($contrast > 0 && $contrast < 5)) {
                    $path = '../files/users/album/' . $user['id'] . '/';
                    require('../incfiles/lib/class.upload.php');
                    $handle = new upload($path . $res['img_name']);
                    // Обрабатываем основное изображение
                    $handle->file_new_name_body = 'img_' . time();
                    if ($rotate == 1 || $rotate == 2)
                        $handle->image_rotate = ($rotate == 2 ? 90 : 270);
                    if ($brightness > 0 && $brightness < 5) {
                        switch ($brightness) {
                            case 1:
                                $handle->image_brightness = -40;
                                break;

                            case 2:
                                $handle->image_brightness = -20;
                                break;

                            case 3:
                                $handle->image_brightness = 20;
                                break;

                            case 4:
                                $handle->image_brightness = 40;
                                break;
                        }
                    }
                    if ($contrast > 0 && $contrast < 5) {
                        switch ($contrast) {
                            case 1:
                                $handle->image_contrast = -50;
                                break;

                            case 2:
                                $handle->image_contrast = -25;
                                break;

                            case 3:
                                $handle->image_contrast = 25;
                                break;

                            case 4:
                                $handle->image_contrast = 50;
                                break;
                        }
                    }
                    $handle->process($path);
                    $img_name = $handle->file_dst_name;
                    if ($handle->processed) {
                        // Обрабатываем превьюшку
                        $handle->file_new_name_body = 'tmb_' . time();
                        if ($rotate == 1 || $rotate == 2)
                            $handle->image_rotate = ($rotate == 2 ? 90 : 270);
                        if ($brightness > 0 && $brightness < 5) {
                            switch ($brightness) {
                                case 1:
                                    $handle->image_brightness = -40;
                                    break;

                                case 2:
                                    $handle->image_brightness = -20;
                                    break;

                                case 3:
                                    $handle->image_brightness = 20;
                                    break;

                                case 4:
                                    $handle->image_brightness = 40;
                                    break;
                            }
                        }
                        if ($contrast > 0 && $contrast < 5) {
                            switch ($contrast) {
                                case 1:
                                    $handle->image_contrast = -50;
                                    break;

                                case 2:
                                    $handle->image_contrast = -25;
                                    break;

                                case 3:
                                    $handle->image_contrast = 25;
                                    break;

                                case 4:
                                    $handle->image_contrast = 50;
                                    break;
                            }
                        }
                        $handle->image_resize = true;
                        $handle->image_x = 100;
                        $handle->image_y = 100;
                        $handle->image_ratio_no_zoom_in = true;
                        $handle->process($path);
                        $tmb_name = $handle->file_dst_name;
                    }
                    $handle->clean();
                    @unlink('../files/users/album/' . $user['id'] . '/' . $res['img_name']);
                    @unlink('../files/users/album/' . $user['id'] . '/' . $res['tmb_name']);
                    $sql = "`img_name` = ?, `tmb_name` = ?,";
                    $ph[] = $img_name;
                    $ph[] = $tmb_name;
                }
                $ph[] = $description;
                $stmt = $db->prepare("UPDATE `cms_album_files` SET $sql
                    `description` = ?
                    WHERE `id` = '$img'
                ");
                $stmt->execute($ph);
            }
            echo '<div class="gmenu"><p>' . $lng_profile['image_edited'] . '<br />' .
                '<a href="album.php?act=show&amp;al=' . $album . '&amp;user=' . $user['id'] . '">' . $lng['continue'] . '</a></p></div>';
        } else {
            unset($_SESSION['post']);
            echo '<form action="album.php?act=image_edit&amp;img=' . $img . '&amp;user=' . $user['id'] . '" method="post">' .
                '<div class="menu">' .
                '<p><h3>' . $lng_profile['image'] . '</h3>' .
                '<img src="../files/users/album/' . $user['id'] . '/' . $res['tmb_name'] . '" /></p>' .
                '<p><h3>' . $lng['description'] . '</h3>' .
                '<textarea name="description" rows="' . $set_user['field_h'] . '">' . functions::checkout($res['description']) . '</textarea><br />' .
                '<small>' . $lng['not_mandatory_field'] . ', max. 500</small></p>' .
                '</div><div class="rmenu">' .
                '<p><h3>Яркость</h3>' .
                '<table border="0" cellspacing="0" cellpadding="0" style="text-align:center"><tr>' .
                '<td><input type="radio" name="brightness" value="1"/></td>' .
                '<td><input type="radio" name="brightness" value="2"/></td>' .
                '<td><input type="radio" name="brightness" value="0" checked="checked"/></td>' .
                '<td><input type="radio" name="brightness" value="3"/></td>' .
                '<td><input type="radio" name="brightness" value="4"/></td>' .
                '</tr><tr>' .
                '<td>-2</td>' .
                '<td>-1</td>' .
                '<td>0</td>' .
                '<td>+1</td>' .
                '<td>+2</td>' .
                '</tr></table></p>' .
                '<p><h3>Контрастность</h3>' .
                '<table border="0" cellspacing="0" cellpadding="0" style="text-align:center"><tr>' .
                '<td><input type="radio" name="contrast" value="1"/></td>' .
                '<td><input type="radio" name="contrast" value="2"/></td>' .
                '<td><input type="radio" name="contrast" value="0" checked="checked"/></td>' .
                '<td><input type="radio" name="contrast" value="3"/></td>' .
                '<td><input type="radio" name="contrast" value="4"/></td>' .
                '</tr><tr>' .
                '<td>-2</td>' .
                '<td>-1</td>' .
                '<td>0</td>' .
                '<td>+1</td>' .
                '<td>+2</td>' .
                '</tr></table></p>' .
                '<p><h3>' . $lng_profile['image_rotate'] . '</h3>' .
                '<input type="radio" name="rotate" value="0" checked="checked"/>&#160;' . $lng_profile['image_rotate_not'] . '<br />' .
                '<input type="radio" name="rotate" value="2"/>&#160;' . $lng_profile['image_rotate_right'] . '<br />' .
                '<input type="radio" name="rotate" value="1"/>&#160;' . $lng_profile['image_rotate_left'] . '</p>' .
                '<p><small>' . $lng_profile['image_edit_warning'] . '</small></p>' .
                '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
                '</div></form>' .
                '<div class="phdr"><a href="album.php?act=show&amp;al=' . $album . '&amp;user=' . $user['id'] . '">' . $lng['cancel'] . '</a></div>';
        }
    } else {
        echo functions::display_error($lng['error_wrong_data']);
    }
}
?>