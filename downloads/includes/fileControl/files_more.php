<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';

// Дополнительные файлы
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($rights < 6 && $rights != 4)) {
    echo '<a href="' . $url . '">' . _t('Downloads') . '</a>';
    exit;
}

$del = isset($_GET['del']) ? abs(intval($_GET['del'])) : false;
$edit = isset($_GET['edit']) ? abs(intval($_GET['edit'])) : false;

if ($edit) {
    // Изменяем файл
    $name_link = isset($_POST['name_link']) ? htmlspecialchars(mb_substr($_POST['name_link'], 0, 200)) : null;
    $req_file_more = $db->query("SELECT `rus_name` FROM `download__more` WHERE `id` = '$edit' LIMIT 1");

    if ($name_link && $req_file_more->rowCount() && isset($_POST['submit'])) {
        $stmt = $db->prepare("
            UPDATE `download__more` SET
            `rus_name` = ?
            WHERE `id` = ?
        ");

        $stmt->execute([
            $name_link,
            $edit,
        ]);

        header('Location: ' . $url . '?act=files_more&id=' . $id);
    } else {
        $res_file_more = $req_file_more->fetch();
        echo '<div class="phdr"><b>' . htmlspecialchars($res_down['rus_name']) . '</b></div>' .
            '<div class="gmenu"><b>' . $lng['edit_file'] . '</b></div>' .
            '<div class="list1"><form action="' . $url . '?act=files_more&amp;id=' . $id . '&amp;edit=' . $edit . '"  method="post">' .
            $lng['link_file'] . ' (мах. 200)<span class="red">*</span>:<br />' .
            '<input type="text" name="name_link" value="' . $res_file_more['rus_name'] . '"/><br />' .
            '<input type="submit" name="submit" value="' . $lng['sent'] . '"/></form>' .
            '</div><div class="phdr"><a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
    }
} else {
    if ($del) {
        // Удаление файла
        $req_file_more = $db->query("SELECT `name` FROM `download__more` WHERE `id` = '$del'");

        if ($req_file_more->rowCount() && isset($_GET['yes'])) {
            $res_file_more = $req_file_more->fetch();

            if (is_file($res_down['dir'] . '/' . $res_file_more['name'])) {
                unlink($res_down['dir'] . '/' . $res_file_more['name']);
            }

            $db->exec("DELETE FROM `download__more` WHERE `id` = '$del' LIMIT 1");
            header('Location: ' . $url . '?act=files_more&id=' . $id);
        } else {
            echo '<div class="rmenu">' . $lng['delete_confirmation'] . '<br /> <a href="' . $url . '?act=files_more&amp;id=' . $id . '&amp;del=' . $del . '&amp;yes">' . $lng['delete'] . '</a> | <a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . $lng['cancel'] . '</a></div>';
        }
    } else {
        if (isset($_POST['submit'])) {
            // Выгружаем файл
            $error = [];
            $link_file = isset($_POST['link_file']) ? str_replace('./', '_', trim($_POST['link_file'])) : null;
            $do_file = false;

            if ($link_file) {
                if (mb_substr($link_file, 0, 7) !== 'http://') {
                    $error[] = $lng['error_link_import'];
                } else {
                    $link_file = str_replace('http://', '', $link_file);

                    if ($link_file) {
                        $do_file = true;
                        $fname = basename($link_file);
                        $fsize = 0;
                    } else {
                        $error[] = $lng['error_link_import'];
                    }
                }

                if ($error) {
                    $error[] = '<a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
                    echo $error;
                    exit;
                }
            } elseif ($_FILES['fail']['size'] > 0) {
                $do_file = true;
                $fname = strtolower($_FILES['fail']['name']);
                $fsize = $_FILES['fail']['size'];
            }

            if ($do_file) {
                $new_file = isset($_POST['new_file']) ? trim($_POST['new_file']) : null;
                $name_link = isset($_POST['name_link']) ? htmlspecialchars(mb_substr($_POST['name_link'], 0, 200)) : null;
                $ext = explode(".", $fname);

                if (!empty($new_file)) {
                    $fname = strtolower($new_file . '.' . $ext[1]);
                    $ext = explode(".", $fname);
                }

                if (empty($name_link)) {
                    $error[] = _t('The required fields are not filled');
                }

                if ($fsize > 1024 * App::cfg()->sys->filesize && !$link_file) {
                    $error[] = $lng['error_file_size'] . ' ' . App::cfg()->sys->filesize . 'kb.';
                }

                if (!in_array($ext[(count($ext) - 1)], $defaultExt)) {
                    $error[] = $lng['error_file_ext'] . ': ' . implode(', ', $defaultExt);
                }

                if (strlen($fname) > 100) {
                    $error[] = $lng['error_file_name_size '];
                }

                if (preg_match("/[^\da-zA-Z_\-.]+/", $fname)) {
                    $error[] = $lng['error_file_symbols'];
                }

                if ($error) {
                    $error[] = '<a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
                    echo $error;
                } else {
                    $newFile = 'file' . $id . '_' . $fname;

                    if (file_exists($res_down['dir'] . '/' . $newFile)) {
                        $fname = 'file' . $id . '_' . time() . $fname;
                    } else {
                        $fname = $newFile;
                    }

                    if ($link_file) {
                        $up_file = copy('http://' . $link_file, "$res_down[dir]/$fname");
                        $fsize = filesize("$res_down[dir]/$fname");
                    } else {
                        $up_file = move_uploaded_file($_FILES["fail"]["tmp_name"], "$res_down[dir]/$fname");
                    }

                    if ($up_file == true) {
                        @chmod("$fname", 0777);
                        @chmod("$res_down[dir]/$fname", 0777);
                        echo '<div class="gmenu">' . $lng['upload_file_ok'] . '<br />' .
                            '<a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . $lng['upload_file_more'] . '</a> | <a href="' . $url . '?id=' . $id . '&amp;act=view">' . _t('Back') . '</a></div>';

                        $stmt = $db->prepare("
                          INSERT INTO `download__more`
                          (`refid`, `time`, `name`, `rus_name`, `size`)
                          VALUES (?, ?, ?, ?, ?)
                        ");

                        $stmt->execute([
                            $id,
                            time(),
                            $fname,
                            $name_link,
                            intval($fsize),
                        ]);
                    } else {
                        echo '<div class="rmenu">' . $lng['upload_file_no'] . '<br /><a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
                    }
                }
            } else {
                echo '<div class="rmenu">' . $lng['upload_file_no'] . '<br /><a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
            }
        } else {
            // Выводим форму
            echo '<div class="phdr"><b>' . $lng['files_more'] . ':</b> ' . htmlspecialchars($res_down['rus_name']) . '</div>' .
                '<div class="menu"><form action="' . $url . '?act=files_more&amp;id=' . $id . '"  method="post" enctype="multipart/form-data">' .
                $lng['select_file'] . '<span class="red">*</span>::<br /><input type="file" name="fail"/><br />' .
                $lng['or_link_to_it'] . ':<br /><input type="post" name="link_file" value=""/><br />' .
                $lng['save_name_file'] . ':<br /><input type="text" name="new_file"/><br />' .
                $lng['link_file'] . ' (мах. 200)<span class="red">*</span>:<br />' .
                '<input type="text" name="name_link" value="' . $lng['download_file_more'] . '"/><br />' .
                '<input type="submit" name="submit" value="' . $lng['upload'] . '"/>' .
                '</form></div>' .
                '<div class="phdr"><small>' . $lng['file_size_faq'] . ' ' . App::cfg()->sys->filesize . 'kb<br />' .
                $lng['extensions'] . ': ' . implode(', ', $defaultExt) . ($set_down['screen_resize'] ? '<br />' . $lng['add_screen_faq'] : '') . '</small></div>';

            // Дополнительные файлы
            $req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = " . $id);
            $total_file = $req_file_more->rowCount();
            $i = 0;

            if ($total_file) {
                while ($res_file_more = $req_file_more->fetch()) {
                    $format = explode('.', $res_file_more['name']);
                    $format_file = strtolower($format[count($format) - 1]);
                    echo(($i++ % 2) ? '<div class="list2">' : '<div class="list1">');
                    echo '<b>' . $res_file_more['rus_name'] . '</b>' .
                        '<div class="sub">' . $res_file_more['name'] . ' (' . Download::displayFileSize($res_file_more['size']) . '), ' . functions::displayDate($res_file_more['time']) . '<br />' .
                        '<a href="' . $url . '?act=files_more&amp;id=' . $id . '&amp;edit=' . $res_file_more['id'] . '">' . $lng['edit'] . '</a> | ' .
                        '<span class="red"><a href="' . $url . '?act=files_more&amp;id=' . $id . '&amp;del=' . $res_file_more['id'] . '">' . $lng['delete'] . '</a></span></div></div>';
                }

                echo '<div class="phdr">' . $lng['total'] . ': ' . $total_file . '</div>';
            }
            echo '<p><a href="' . $url . '?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></p>';
        }
    }
}
