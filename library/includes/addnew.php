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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

use Library\Hashtags;

if (($adm || ($db->query("SELECT `user_add` FROM `library_cats` WHERE `id`=" . $id)->rowCount() > 0) && isset($id) && $systemUser->isValid())) {
    // Проверка на флуд
    $flood = $tools->antiflood();

    if ($flood) {
        require('../system/head.php');

        echo $tools->displayError(sprintf(_t('You cannot add the Article so often<br>Please, wait %d sec.'), $flood),
            '<br><a href="?do=dir&amp;id=' . $id . '">' . _t('Back') . '</a>');
        require('../system/end.php');
        exit;
    }

    $name = isset($_POST['name']) ? mb_substr(trim($_POST['name']), 0, 100) : '';
    $announce = isset($_POST['announce']) ? mb_substr(trim($_POST['announce']), 0, 500) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $tag = isset($_POST['tags']) ? trim($_POST['tags']) : '';

    if (isset($_POST['submit'])) {
        $err = [];

        if (empty($_POST['name'])) {
            $err[] = _t('You have not entered the name');
        }

        if (!empty($_FILES['textfile']['name'])) {
            $ext = explode('.', $_FILES['textfile']['name']);
            if (mb_strtolower(end($ext)) == 'txt') {
                $newname = $_FILES['textfile']['name'];
                if (move_uploaded_file($_FILES['textfile']['tmp_name'], '../files/library/tmp/' . $newname)) {
                    $txt = file_get_contents('../files/library/tmp/' . $newname);
                    if (mb_check_encoding($txt, 'UTF-8')) {
                    } elseif (mb_check_encoding($txt, 'windows-1251')) {
                        $txt = iconv('windows-1251', 'UTF-8', $txt);
                    } elseif (mb_check_encoding($txt, 'KOI8-R')) {
                        $txt = iconv('KOI8-R', 'UTF-8', $txt);
                    } else {
                        echo $tools->displayError(_t('The file is invalid encoding, preferably UTF-8') . '<br><a href="?act=addnew&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
                        require_once('../system/end.php');
                        exit;
                    }

                    $text = trim($txt);
                    unlink('../files/library/tmp' . DIRECTORY_SEPARATOR . $newname);
                } else {
                    echo $tools->displayError(_t('Error uploading') . '<br><a href="?act=addnew&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
                    require_once('../system/end.php');
                    exit;
                }
            } else {
                echo $tools->displayError(_t('Invalid file format allowed * .txt') . '<br><a href="?act=addnew&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
                require_once('../system/end.php');
                exit;
            }
        } elseif (!empty($_POST['text'])) {
            $text = trim($_POST['text']);
        } else {
            $err[] = _t('You have not entered text');
        }

        if (empty($announce)) {
            $announce = mb_substr($text, 0, 500);
        }

        $md = $adm ? 1 : 0;

        if (sizeof($err) > 0) {
            foreach ($err as $e) {
                echo $tools->displayError($e);
            }
        } else {
            $sql = "
              INSERT INTO `library_texts`
              SET
                `cat_id` = $id,
                `name` = " . $db->quote($name) . ",
                `announce` = " . $db->quote($announce) . ",
                `text` = " . $db->quote($text) . ",
                `uploader` = '" . $systemUser->name . "',
                `uploader_id` = " . $systemUser->id . ",
                `premod` = $md,
                `comments` = " . (isset($_POST['comments']) ? 1 : 0) . ",
                `time` = " . time() . "
            ";

            if ($db->query($sql)) {
                $cid = $db->lastInsertId();

                $handle = new upload($_FILES['image']);
                if ($handle->uploaded) {
                    // Обрабатываем фото
                    $handle->file_new_name_body = $cid;
                    $handle->allowed = [
                        'image/jpeg',
                        'image/gif',
                        'image/png',
                    ];
                    $handle->file_max_size = 1024 * $config['flsz'];
                    $handle->file_overwrite = true;
                    $handle->image_x = $handle->image_src_x;
                    $handle->image_y = $handle->image_src_y;
                    $handle->image_convert = 'png';
                    $handle->process('../files/library/images/orig/');
                    $err_image = $handle->error;
                    $handle->file_new_name_body = $cid;
                    $handle->file_overwrite = true;

                    if ($handle->image_src_y > 240) {
                        $handle->image_resize = true;
                        $handle->image_x = 240;
                        $handle->image_y = $handle->image_src_y * (240 / $handle->image_src_x);
                    } else {
                        $handle->image_x = $handle->image_src_x;
                        $handle->image_y = $handle->image_src_y;
                    }

                    $handle->image_convert = 'png';
                    $handle->process('../files/library/images/big/');
                    $err_image = $handle->error;
                    $handle->file_new_name_body = $cid;
                    $handle->file_overwrite = true;
                    $handle->image_resize = true;
                    $handle->image_x = 32;
                    $handle->image_y = 32;
                    $handle->image_convert = 'png';
                    $handle->process('../files/library/images/small/');

                    if ($err_image) {
                        echo $tools->displayError(_t('Photo uploading error') . '<br><a href="?act=addnew&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
                    }
                    $handle->clean();
                }

                if (!empty($_POST['tags'])) {
                    $tags = array_map('trim', explode(',', $_POST['tags']));
                    if (sizeof($tags > 0)) {
                        $obj = new Hashtags($cid);
                        $obj->addTags($tags);
                        $obj->delCache();
                    }
                }

                echo '<div>' . _t('Article added') . '</div>' . ($md == 0 ? '<div>' . _t('Thank you for what we have written. After checking moderated, your Article will be published in the library.') . '</div>' : '');
                $db->exec("UPDATE `users` SET `lastpost` = " . time() . " WHERE `id` = " . $systemUser->id);
                echo $md == 1 ? '<div><a href="index.php?id=' . $cid . '">' . _t('To Article') . '</a></div>' : '<div><a href="?do=dir&amp;id=' . $id . '">' . _t('To Section') . '</a></div>';
                require_once('../system/end.php');
                exit;
            } else {
                echo $db->errorInfo();
//                exit;
            }
        }
    }
    echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Write Article') . '</div>'
        . '<form name="form" enctype="multipart/form-data" action="?act=addnew&amp;id=' . $id . '" method="post">'
        . '<div class="menu">'
        . '<p><h3>' . _t('Title') . ' (max. 100):</h3>'
        . '<input type="text" name="name" value="' . $name . '" /></p>'
        . '<p><h3>' . _t('Announce') . ' (max. 500):</h3>'
        . '<textarea name="announce" rows="2" cols="20">' . $announce . '</textarea></p>'
        . '<p><h3>' . _t('Text') . ':</h3>'
        . $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form',
            'text') . '<textarea name="text" rows="' . $systemUser->getConfig()->fieldHeight . '" cols="20">' . $text . '</textarea></p>'
        . '<p><input type="checkbox" name="comments" value="1" checked="checked" />' . _t('Commenting on the Article') . '</p>'
        . '<p><h3>' . _t('To upload a photo') . '</h3>'
        . '<input type="file" name="image" accept="image/*" /></p>'
        . '<p><h3>' . _t('Select the text file') . '</h3>'
        . '<input type="file" name="textfile" accept="text/plain" /><br><small>' . _t('Text entry field will be ignored') . '</small></p>'
        . '<p><h3>' . _t('Tags') . '</h3>'
        . '<input name="tags" type="text" value="' . $tag . '" /><br><small>' . _t('Specify the Tag to the Article, separated by commas') . '</small></p>'
        . '<p><input type="submit" name="submit" value="' . _t('Save') . '" /></p>'
        . '</div></form>'
        . '<div class="phdr"><a href="?do=dir&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
} else {
    header('location: ?');
}
