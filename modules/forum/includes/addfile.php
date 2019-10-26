<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

require 'system/head.php';

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

if (! $id || ! $systemUser->isValid()) {
    echo $tools->displayError(_t('Wrong data'));
    require 'system/end.php';
    exit;
}

// Проверяем, тот ли юзер заливает файл и в нужное ли место
$res = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '${id}'")->fetch();

if (empty($res) || $res['user_id'] != $systemUser->id) {
    echo $tools->displayError(_t('Wrong data'));
    require 'system/end.php';
    exit;
}

// Проверяем лимит времени, отведенный для выгрузки файла
if ($res['date'] < (time() - 3600)) {
    echo $tools->displayError(_t('The time allotted for the file upload has expired'), '<a href="?&typ=topic&id=' . $res['topic_id'] . '&amp;page=' . $page . '">' . _t('Back') . '</a>');
    require 'system/end.php';
    exit;
}

if (isset($_POST['submit'])) {
    // Проверка, был ли выгружен файл и с какого браузера
    $do_file = false;
    $file = '';

    if ($_FILES['fail']['size'] > 0) {
        // Проверка загрузки с обычного браузера
        $do_file = true;
        $file = $tools->rusLat($_FILES['fail']['name']);
        $fsize = $_FILES['fail']['size'];
    }

    // Обработка файла (если есть), проверка на ошибки
    if ($do_file) {
        // Список допустимых расширений файлов.
        $al_ext = array_merge($ext_win, $ext_java, $ext_sis, $ext_doc, $ext_pic, $ext_arch, $ext_video, $ext_audio, $ext_other);
        $ext = explode('.', $file);
        $error = [];

        // Проверка на допустимый размер файла
        if ($fsize > 1024 * $config['flsz']) {
            $error[] = _t('File size exceed') . ' ' . $config['flsz'] . 'kb.';
        }

        // Проверка файла на наличие только одного расширения
        if (count($ext) != 2) {
            $error[] = _t('You may upload only files with a name and one extension <b>(name.ext</b>). Files without a name, extension, or with double extension are forbidden.');
        }

        // Проверка допустимых расширений файлов
        if (! in_array($ext[1], $al_ext)) {
            $error[] = _t('The forbidden file format.<br>You can upload files of the following extension') . ':<br>' . implode(', ', $al_ext);
        }

        // Обработка названия файла
        if (mb_strlen($ext[0]) == 0) {
            $ext[0] = '---';
        }

        $ext[0] = str_replace(' ', '_', $ext[0]);
        $fname = mb_substr($ext[0], 0, 32) . '.' . $ext[1];

        // Проверка на запрещенные символы
        if (preg_match("/[^\da-z_\-.]+/", $fname)) {
            $error[] = _t('File name contains invalid characters');
        }

        // Проверка наличия файла с таким же именем
        if (file_exists(UPLOAD_PATH . 'forum/attach/' . $fname)) {
            $fname = time() . $fname;
        }

        // Окончательная обработка
        if (! $error && $do_file) {
            // Для обычного браузера
            if ((move_uploaded_file($_FILES['fail']['tmp_name'], UPLOAD_PATH . 'forum/attach/' . $fname)) == true) {
                @chmod("${fname}", 0777);
                @chmod(UPLOAD_PATH . 'forum/attach/' . $fname, 0777);
                echo _t('File attached') . '<br>';
            } else {
                $error[] = _t('Error uploading file');
            }
        }

        if (! $error) {
            // Определяем тип файла
            $ext = strtolower($ext[1]);
            if (in_array($ext, $ext_win)) {
                $type = 1;
            } elseif (in_array($ext, $ext_java)) {
                $type = 2;
            } elseif (in_array($ext, $ext_sis)) {
                $type = 3;
            } elseif (in_array($ext, $ext_doc)) {
                $type = 4;
            } elseif (in_array($ext, $ext_pic)) {
                $type = 5;
            } elseif (in_array($ext, $ext_arch)) {
                $type = 6;
            } elseif (in_array($ext, $ext_video)) {
                $type = 7;
            } elseif (in_array($ext, $ext_audio)) {
                $type = 8;
            } else {
                $type = 9;
            }

            // Определяем ID субкатегории и категории
            $res2 = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
            $res3 = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res2['section_id'] . "'")->fetch();

            // Заносим данные в базу
            $db->exec("
              INSERT INTO `cms_forum_files` SET
              `cat` = '" . $res3['parent'] . "',
              `subcat` = '" . $res2['section_id'] . "',
              `topic` = '" . $res['topic_id'] . "',
              `post` = '${id}',
              `time` = '" . $res['date'] . "',
              `filename` = " . $db->quote($fname) . ",
              `filetype` = '${type}'
            ");
        } else {
            echo $tools->displayError($error, '<a href="?act=addfile&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
        }
    } else {
        echo  $tools->displayError(_t('Error uploading file'), '<a href="?act=addfile&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
    }

    $pa2 = $db->query("SELECT `id` FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "'")->rowCount();
    $page = ceil($pa2 / $kmess);
    echo '<br><a href="?type=topic&id=' . $res['topic_id'] . '&amp;page=' . $page . '">' . _t('Continue') . '</a><br>';
} else {
    // Форма выбора файла для выгрузки
    echo '<div class="phdr"><b>' . _t('Add File') . '</b></div>'
        . '<div class="gmenu"><form action="?act=addfile&amp;id=' . $id . '" method="post" enctype="multipart/form-data"><p>'
        . '<input type="file" name="fail"/>'
        . '</p><p><input type="submit" name="submit" value="' . _t('Upload') . '"/></p></form></div>'
        . '<div class="phdr">' . _t('Max. Size') . ': ' . $config['flsz'] . 'kb.</div>';
}
