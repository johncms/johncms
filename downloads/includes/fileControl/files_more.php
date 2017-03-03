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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

require '../system/head.php';

// Дополнительные файлы
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name']) || ($systemUser->rights < 6 && $systemUser->rights != 4)) {
    echo '<a href="?">' . _t('Downloads') . '</a>';
    require '../system/end.php';
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

        header('Location: ?act=files_more&id=' . $id);
    } else {
        $res_file_more = $req_file_more->fetch();
        echo '<div class="phdr"><b>' . htmlspecialchars($res_down['rus_name']) . '</b></div>' .
            '<div class="gmenu"><b>' . _t('Edit File') . '</b></div>' .
            '<div class="list1"><form action="?act=files_more&amp;id=' . $id . '&amp;edit=' . $edit . '"  method="post">' .
            _t('Link to download file') . ' (мах. 200)<span class="red">*</span>:<br>' .
            '<input type="text" name="name_link" value="' . $res_file_more['rus_name'] . '"/><br>' .
            '<input type="submit" name="submit" value="' . _t('Save') . '"/></form>' .
            '</div><div class="phdr"><a href="?act=files_more&amp;id=' . $id . '">' . _t('Back') . '</a></div>';
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
            header('Location: ?act=files_more&id=' . $id);
        } else {
            echo '<div class="rmenu">' . _t('Do you really want to delete?') . '<br> <a href="?act=files_more&amp;id=' . $id . '&amp;del=' . $del . '&amp;yes">' . _t('Delete') . '</a> | <a href="?act=files_more&amp;id=' . $id . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        if (isset($_POST['submit'])) {
            // Выгружаем файл
            $error = [];
            $link_file = isset($_POST['link_file']) ? str_replace('./', '_', trim($_POST['link_file'])) : null;
            $do_file = false;

            if ($link_file) {
                if (mb_substr($link_file, 0, 7) !== 'http://') {
                    $error[] = _t('Invalid Link');
                } else {
                    $link_file = str_replace('http://', '', $link_file);

                    if ($link_file) {
                        $do_file = true;
                        $fname = basename($link_file);
                        $fsize = 0;
                    } else {
                        $error[] = _t('Invalid Link');
                    }
                }

                if ($error) {
                    $error[] = '<a href="?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
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

                if ($fsize > 1024 * $config['flsz'] && !$link_file) {
                    $error[] = _t('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
                }

                if (!in_array($ext[(count($ext) - 1)], $defaultExt)) {
                    $error[] = _t('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $defaultExt);
                }

                if (strlen($fname) > 100) {
                    $error[] = _t('The file name length must not exceed 100 characters');
                }

                if (preg_match("/[^\da-zA-Z_\-.]+/", $fname)) {
                    $error[] = _t('The file name contains invalid characters');
                }

                if ($error) {
                    $error[] = '<a href="?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
                    echo implode('<br>', $error);
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
                        echo '<div class="gmenu">' . _t('File attached') . '<br>' .
                            '<a href="?act=files_more&amp;id=' . $id . '">' . _t('Upload more') . '</a> | <a href="?id=' . $id . '&amp;act=view">' . _t('Back') . '</a></div>';

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
                        echo '<div class="rmenu">' . _t('File not attached') . '<br><a href="?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
                    }
                }
            } else {
                echo '<div class="rmenu">' . _t('File not attached') . '<br><a href="?act=files_more&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
            }
        } else {
            // Выводим форму
            echo '<div class="phdr"><b>' . _t('Additional Files') . ':</b> ' . htmlspecialchars($res_down['rus_name']) . '</div>' .
                '<div class="menu"><form action="?act=files_more&amp;id=' . $id . '"  method="post" enctype="multipart/form-data">' .
                _t('Select File') . '<span class="red">*</span>::<br><input type="file" name="fail"/><br>' .
                _t('Or link to it') . ':<br><input type="post" name="link_file" value=""/><br>' .
                _t('Save as (max. 30, without extension)') . ':<br><input type="text" name="new_file"/><br>' .
                _t('Link to download file') . ' (мах. 200)<span class="red">*</span>:<br>' .
                '<input type="text" name="name_link" value="' . _t('Download the additional file') . '"/><br>' .
                '<input type="submit" name="submit" value="' . _t('Upload') . '"/>' .
                '</form></div>' .
                '<div class="phdr"><small>' . _t('File weight should not exceed') . ' ' . $config['flsz'] . 'kb<br>' .
                _t('Allowed extensions') . ': ' . implode(', ', $defaultExt) . ($set_down['screen_resize'] ? '<br>' . _t('A screenshot is automatically converted to a picture, of a width not exceeding 240px (height will be calculated automatically)') : '') . '</small></div>';

            // Дополнительные файлы
            $req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = " . $id);
            $total_file = $req_file_more->rowCount();
            $i = 0;

            if ($total_file) {
                require 'classes/download.php';

                while ($res_file_more = $req_file_more->fetch()) {
                    $format = explode('.', $res_file_more['name']);
                    $format_file = strtolower($format[count($format) - 1]);
                    echo(($i++ % 2) ? '<div class="list2">' : '<div class="list1">');
                    echo '<b>' . $res_file_more['rus_name'] . '</b>' .
                        '<div class="sub">' . $res_file_more['name'] . ' (' . Download::displayFileSize($res_file_more['size']) . '), ' . $tools->displayDate($res_file_more['time']) . '<br>' .
                        '<a href="?act=files_more&amp;id=' . $id . '&amp;edit=' . $res_file_more['id'] . '">' . _t('Edit') . '</a> | ' .
                        '<span class="red"><a href="?act=files_more&amp;id=' . $id . '&amp;del=' . $res_file_more['id'] . '">' . _t('Delete') . '</a></span></div></div>';
                }

                echo '<div class="phdr">' . _t('Total') . ': ' . $total_file . '</div>';
            }
            echo '<p><a href="?act=view&amp;id=' . $id . '">' . _t('Back') . '</a></p>';
        }
    }
}

require '../system/end.php';
