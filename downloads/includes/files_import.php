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

if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    require '../system/head.php';

    $req = $db->query("SELECT * FROM `download__category` WHERE `id` = " . $id);
    $res = $req->fetch();

    if (!$req->rowCount() || !is_dir($res['dir'])) {
        echo _t('The directory does not exist') . '<a href="?">' . _t('Downloads') . '</a>';
        require '../system/end.php';
        exit;
    }

    $al_ext = $res['field'] ? explode(', ', $res['text']) : $defaultExt;

    if (isset($_POST['submit'])) {
        $load_cat = $res['dir'];
        $error = [];
        $url = isset($_POST['fail']) ? str_replace('./', '_', trim($_POST['fail'])) : null;

        if ($url) {
            if (mb_substr($url, 0, 7) !== 'http://') {
                $error[] = _t('Invalid Link');
            } else {
                $url = str_replace('http://', '', $url);
            }
        }

        if ($url && !$error) {
            $fname = basename($url);
            $new_file = isset($_POST['new_file']) ? trim($_POST['new_file']) : null;
            $name = isset($_POST['text']) ? trim($_POST['text']) : null;
            $name_link = isset($_POST['name_link']) ? htmlspecialchars(mb_substr($_POST['name_link'], 0, 200)) : null;
            $text = isset($_POST['opis']) ? trim($_POST['opis']) : null;
            $ext = explode(".", $fname);

            if (!empty($new_file)) {
                $fname = strtolower($new_file . '.' . $ext[1]);
                $ext = explode(".", $fname);
            }

            if (empty($name)) {
                $name = $fname;
            }

            if (empty($name_link)) {
                $error[] = _t('The required fields are not filled');
            }

            if (!in_array($ext[(count($ext) - 1)], $al_ext)) {
                $error[] = _t('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $al_ext);
            }

            if (strlen($fname) > 100) {
                $error[] = _t('The file name length must not exceed 100 characters');
            }

            if (preg_match("/[^\da-zA-Z_\-.]+/", $fname)) {
                $error[] = _t('The file name contains invalid characters');
            }
        } elseif (!$url) {
            $error[] = _t('Invalid Link');
        }

        if ($error) {
            $error[] = '<a href="?act=import&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
            echo $error;
        } else {
            if (file_exists("$load_cat/$fname")) {
                $fname = time() . $fname;
            }

            if (copy('http://' . $url, "$load_cat/$fname")) {
                echo '<div class="phdr"><b>' . _t('File import') . ': ' . htmlspecialchars($res['rus_name']) . '</b></div>';
                echo '<div class="gmenu">' . _t('File attached') . '</div>';

                $stmt = $db->prepare("
                    INSERT INTO `download__files`
                    (`refid`, `dir`, `time`, `name`, `text`, `rus_name`, `type`, `user_id`, `about`, `desc`)
                    VALUES (?, ?, ?, ?, ?, ?, 2, ?, ?, '')
                ");

                $stmt->execute([
                    $id,
                    $load_cat,
                    time(),
                    $fname,
                    $name_link,
                    mb_substr($name, 0, 200),
                    $systemUser->id,
                    $text,
                ]);
                $file_id = $db->lastInsertId();

                $handle = new upload($_FILES['screen']);

                if ($handle->uploaded) {
                    if (mkdir(DOWNLOADS_SCR . $file_id, 0777) == true) {
                        @chmod(DOWNLOADS_SCR . $file_id, 0777);
                    }

                    $handle->file_new_name_body = $file_id;
                    $handle->allowed = [
                        'image/jpeg',
                        'image/gif',
                        'image/png',
                    ];
                    $handle->file_max_size = 1024 * $config->flsz;
                    $handle->file_overwrite = true;

                    if ($set_down['screen_resize']) {
                        $handle->image_resize = true;
                        $handle->image_x = 240;
                        $handle->image_ratio_y = true;
                    }

                    $handle->process(DOWNLOADS_SCR . $file_id . '/');

                    if ($handle->processed) {
                        echo '<div class="rmenu">' . _t('Screenshot is attached') . '</div>';
                    } else {
                        echo '<div class="rmenu">' . _t('Screenshot not attached') . ': ' . $handle->error . '</div>';
                    }
                }

                echo '<div class="menu"><a href="?act=view&amp;id=' . $file_id . '">' . _t('Continue') . '</a></div>';
                $dirid = $id;
                $sql = '';
                $i = 0;

                while ($dirid != '0' && $dirid != "") {
                    $res_down = $db->query("SELECT `refid` FROM `download__category` WHERE `id` = '$dirid' LIMIT 1")->fetch();
                    if ($i) {
                        $sql .= ' OR ';
                    }
                    $sql .= '`id` = \'' . $dirid . '\'';
                    $dirid = $res_down['refid'];
                    ++$i;
                }

                $db->exec("UPDATE `download__category` SET `total` = (`total`+1) WHERE $sql");
                echo '<div class="phdr"><a href="?act=import&amp;id=' . $id . '">' . _t('Upload more') . '</a> | <a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
            } else {
                echo '<div class="rmenu">' . _t('File not attached') . '<br><a href="?act=import&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
            }
        }
    } else {
        echo '<div class="phdr"><b>' . _t('File import') . ': ' . htmlspecialchars($res['rus_name']) . '</b></div>' .
            '<div class="list1"><form action="?act=import&amp;id=' . $id . '" method="post" enctype="multipart/form-data">' .
            _t('Link to file') . '<span class="red">*</span>:<br><input type="post" name="fail" value="http://"/><br>' .
            _t('Save as (max. 30, without extension)') . ':<br><input type="text" name="new_file"/><br>' .
            _t('Screenshot') . ':<br><input type="file" name="screen"/><br>' .
            _t('File Name') . ' (мах. 200):<br><input type="text" name="text"/><br>' .
            _t('Link to download file') . ' (мах. 200)<span class="red">*</span>:<br>' .
            '<input type="text" name="name_link" value="Скачать файл"/><br>' .
            _t('Description') . ' (max. 500)<br><textarea name="opis"></textarea>' .
            '<br><input type="submit" name="submit" value="' . _t('Upload') . '"/></form></div>' .
            '<div class="phdr"><small>' . _t('Allowed extensions') . ': ' . implode(', ', $al_ext) . ($set_down['screen_resize'] ? '<br>' . _t('A screenshot is automatically converted to a picture, of a width not exceeding 240px (height will be calculated automatically)') : '') . '</small></div>' .
            '<p><a href="?id=' . $id . '">' . _t('Back') . '</a></p>';
    }

    require '../system/end.php';
}
