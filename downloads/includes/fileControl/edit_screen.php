<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Управление скриншотами
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($rights < 6 && $rights != 4)) {
    echo '<a href="' . $url . '">' . $lng['download_title'] . '</a>';
    exit;
}

$screen = [];
$do = isset($_GET['do']) ? trim($_GET['do']) : '';

if ($do && is_file($screens_path . '/' . $id . '/' . $do)) {
    // Удаление скриншота
    unlink($screens_path . '/' . $id . '/' . $do);
    header('Location: ' . $url . '?act=edit_screen&id=' . $id);
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
            $handle->file_max_size = 1024 * App::cfg()->sys->filesize;

            if ($set_down['screen_resize']) {
                $handle->image_resize = true;
                $handle->image_x = 240;
                $handle->image_ratio_y = true;
            }

            $handle->process($screens_path . '/' . $id . '/');

            if ($handle->processed) {
                echo '<div class="gmenu"><b>' . $lng['upload_screen_ok'] . '</b>';
            } else {
                echo '<div class="rmenu"><b>' . $lng['upload_screen_no'] . ': ' . $handle->error . '</b>';
            }
        } else {
            echo '<div class="rmenu"><b>' . $lng['upload_screen_no'] . '</b>';
        }

        echo '<br /><a href="' . $url . '?act=edit_screen&amp;id=' . $id . '">' . $lng['upload_file_more'] . '</a>' .
            '<br /><a href="' . $url . '?act=view&amp;id=' . $id . '">' . $lng['back'] . '</a></div>';
    } else {
        // Форма выгрузки
        echo '<div class="phdr"><b>' . $lng['screen_file'] . '</b>: ' . htmlspecialchars($res_down['rus_name']) . '</div>' .
            '<div class="list1"><form action="' . $url . '?act=edit_screen&amp;id=' . $id . '"  method="post" enctype="multipart/form-data"><input type="file" name="screen"/><br />' .
            '<input type="submit" name="submit" value="' . $lng['upload'] . '"/></form></div>' .
            '<div class="phdr"><small>' . $lng['file_size_faq'] . ' ' . App::cfg()->sys->filesize . 'kb' .
            ($set_down['screen_resize'] ? '<br />' . $lng['add_screen_faq'] : '') . '</small></div>';

        // Выводим скриншоты
        $screen = [];

        if (is_dir($screens_path . '/' . $id)) {
            $dir = opendir($screens_path . '/' . $id);

            while ($file = readdir($dir)) {
                if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
                    $screen[] = $screens_path . '/' . $id . '/' . $file;
                }
            }
            closedir($dir);
        } else {
            if (mkdir($screens_path . '/' . $id, 0777) == true) {
                @chmod($screens_path . '/' . $id, 0777);
            }
        }

        if ($screen) {
            $total = count($screen);

            for ($i = 0; $i < $total; $i++) {
                $screen_name = htmlentities($screen[$i], ENT_QUOTES, 'utf-8');
                $file = preg_replace('#^' . $screens_path . '/' . $id . '/(.*?)$#isU', '$1', $screen_name, 1);
                echo (($i % 2) ? '<div class="list2">' : '<div class="list1">') .
                    '<table  width="100%"><tr><td width="40" valign="top">' .
                    '<a href="' . $screen_name . '"><img src="' . App::cfg()->sys->homeurl . 'assets/misc/thumbinal.php?type=1&amp;img=' . rawurlencode($screen_name) . '" alt="screen_' . $i . '" /></a></td><td>' . $file .
                    '<div class="sub"><a href="' . $url . '?act=edit_screen&amp;id=' . $id . '&amp;do=' . $file . '">' . $lng['delete'] . '</a></div></td></tr></table></div>';
            }
        }

        echo '<div class="phdr"><a href="' . $url . '?act=view&amp;id=' . $id . '">' . $lng['back'] . '</a></div>';
    }
}
