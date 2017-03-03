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

require '../system/head.php';

// Управление скриншотами
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($systemUser->rights < 6 && $systemUser->rights != 4)) {
    echo '<a href="?">' . _t('Downloads') . '</a>';
    require '../system/end.php';
    exit;
}

$screen = [];
$do = isset($_GET['do']) ? trim($_GET['do']) : '';

if ($do && is_file(DOWNLOADS_SCR . $id . DIRECTORY_SEPARATOR . $do)) {
    //TODO: Переделать форму удаления на POST запрос
    // Удаление скриншота
    unlink(DOWNLOADS_SCR . $id . DIRECTORY_SEPARATOR . $do);
    header('Location: ?act=edit_screen&id=' . $id);
    exit;
} else {
    if (isset($_POST['submit'])) {
        // Загрузка скриншота
        $handle = new upload($_FILES['screen']);

        if ($handle->uploaded) {
            $handle->file_new_name_body = $id;
            $handle->allowed = [
                'image/jpeg',
                'image/gif',
                'image/png',
            ];
            $handle->file_max_size = 1024 * $config->flsz;

            if ($set_down['screen_resize']) {
                $handle->image_resize = true;
                $handle->image_x = 240;
                $handle->image_ratio_y = true;
            }

            $handle->process(DOWNLOADS_SCR . $id . '/');

            if ($handle->processed) {
                echo '<div class="gmenu"><b>' . _t('Screenshot is attached') . '</b>';
            } else {
                echo '<div class="rmenu"><b>' . _t('Screenshot not attached') . ': ' . $handle->error . '</b>';
            }
        } else {
            echo '<div class="rmenu"><b>' . _t('Screenshot not attached') . '</b>';
        }

        echo '<br><a href="?act=edit_screen&amp;id=' . $id . '">' . _t('Upload more') . '</a>' .
            '<br><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
    } else {
        // Форма выгрузки
        echo '<div class="phdr"><b>' . _t('Screenshot') . '</b>: ' . htmlspecialchars($res_down['rus_name']) . '</div>' .
            '<div class="list1"><form action="?act=edit_screen&amp;id=' . $id . '"  method="post" enctype="multipart/form-data"><input type="file" name="screen"/><br>' .
            '<input type="submit" name="submit" value="' . _t('Upload') . '"/></form></div>' .
            '<div class="phdr"><small>' . _t('File weight should not exceed') . ' ' . $config->flsz . 'kb' .
            ($set_down['screen_resize'] ? '<br>' . _t('A screenshot is automatically converted to a picture, of a width not exceeding 240px (height will be calculated automatically)') : '') . '</small></div>';

        // Выводим скриншоты
        $screen = [];

        if (is_dir(DOWNLOADS_SCR . $id)) {
            $dir = opendir(DOWNLOADS_SCR . $id);

            while ($file = readdir($dir)) {
                if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
                    $screen[] = '../files/downloads/screen/' . $id . '/' . $file;
                }
            }

            closedir($dir);
        } else {
            if (mkdir(DOWNLOADS_SCR . $id, 0777) == true) {
                @chmod(DOWNLOADS_SCR . $id, 0777);
            }
        }

        if (!empty($screen)) {
            $total = count($screen);

            for ($i = 0; $i < $total; $i++) {
                $screen_name = htmlentities($screen[$i], ENT_QUOTES, 'utf-8');
                $file = preg_replace('#^' . DOWNLOADS_SCR . $id . '/(.*?)$#isU', '$1', $screen_name, 1);
                echo (($i % 2) ? '<div class="list2">' : '<div class="list1">') .
                    '<table  width="100%"><tr><td width="40" valign="top">' .
                    '<a href="' . $screen_name . '"><img src="preview.php?type=2&amp;img=' . rawurlencode($screen[$i]) . '" alt="screen" /></a></td><td>' .
                    '<a href="?act=edit_screen&amp;id=' . $id . '&amp;do=' . basename($file) . '">' . _t('Delete') . '</a></td></tr></table></div>';
            }
        }

        echo '<div class="phdr"><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
    }
}

require '../system/end.php';
