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

require_once('../system/head.php');

$req = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
$res = $req->fetch();

if ($req->rowCount() && is_dir($res['dir'])) {
    if (($res['field'] && $systemUser->isValid()) || ($systemUser->rights == 4 || $systemUser->rights >= 6)) {
        $al_ext = $res['field'] ? explode(', ', $res['text']) : $defaultExt;

        if (isset($_POST['submit'])) {
            $load_cat = $res['dir'];
            $do_file = false;

            if ($_FILES['fail']['size'] > 0) {
                $do_file = true;
                $fname = strtolower($_FILES['fail']['name']);
                $fsize = $_FILES['fail']['size'];
            }

            if ($do_file) {
                $error = [];
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

                if ($fsize > 1024 * $config['flsz']) {
                    $error[] = _t('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
                }

                if (!in_array($ext[(count($ext) - 1)], $al_ext)) {
                    $error[] = _t('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $al_ext);
                }

                if (strlen($fname) > 100) {
                    $error[] = _t('The file name length must not exceed 100 characters');
                }

                if (preg_match("/[^\da-z_\-.]+/", $fname)) {
                    $error[] = _t('The file name contains invalid characters');
                }

                if ($error) {
                    $error[] = '<a href="?act=down_file&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
                    echo implode('<br>', $error);
                } else {
                    if (file_exists("$load_cat/$fname")) {
                        $fname = time() . $fname;
                    }

                    if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "$load_cat/$fname")) == true) {
                        echo '<div class="phdr"><b>' . _t('Upload File') . ': ' . htmlspecialchars($res['rus_name']) . '</b></div>';
                        @chmod("$fname", 0777);
                        @chmod("$load_cat/$fname", 0777);
                        echo '<div class="gmenu">' . _t('File attached');

                        if ($set_down['mod'] && ($systemUser->rights < 6 && $systemUser->rights != 4)) {
                            echo _t('If you pass moderation, it will be added to the Downloads');
                            $type = 3;
                        } else {
                            $type = 2;
                        }

                        echo '</div>';

                        $stmt = $db->prepare("
                            INSERT INTO `download__files`
                            (`refid`, `dir`, `time`, `name`, `text`, `rus_name`, `type`, `user_id`, `about`, `desc`)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '')
                        ");

                        $stmt->execute([
                            $id,
                            $load_cat,
                            time(),
                            $fname,
                            $name_link,
                            mb_substr($name, 0, 200),
                            $type,
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
                                echo '<div class="gmenu">' . _t('Screenshot is attached') . '</div>';
                            } else {
                                echo '<div class="rmenu">' . _t('Screenshot not attached') . ': ' . $handle->error . '</div>';
                            }
                        } else {
                            echo '<div class="rmenu">' . _t('Screenshot not attached') . '</div>';
                        }

                        if (!$set_down['mod'] || $systemUser->rights > 6 || $systemUser->rights == 4) {
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
                        }
                        echo '<div class="phdr"><a href="?act=down_file&amp;id=' . $id . '">' . _t('Upload more') . '</a> | <a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
                    } else {
                        echo '<div class="rmenu">' . _t('File not attached') . '<br><a href="?act=down_file&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
                    }
                }
            } else {
                echo '<div class="rmenu">' . _t('File not attached') . '<br><a href="?act=down_file&amp;id=' . $id . '">' . _t('Repeat') . '</a></div>';
            }
        } else {
            echo '<div class="phdr"><b>' . _t('Upload File') . ': ' . htmlspecialchars($res['rus_name']) . '</b></div>' .
                '<div class="list1"><form action="?act=down_file&amp;id=' . $id . '" method="post" enctype="multipart/form-data">' .
                _t('Select File') . '<span class="red">*</span>:<br><input type="file" name="fail"/><br>' .
                _t('Save as (max. 30, without extension)') . ':<br><input type="text" name="new_file"/><br>' .
                _t('Screenshot') . ':<br><input type="file" name="screen"/><br>' .
                _t('File Name') . ' (мах. 200):<br><input type="text" name="text"/><br>' .
                _t('Link to download file') . ' (мах. 200)<span class="red">*</span>:<br><input type="text" name="name_link" value="' . _t('Download') . '"/><br>' .
                _t('Description') . ' (max. 500)<br><textarea name="opis"></textarea><br>' .
                '<input type="submit" name="submit" value="' . _t('Upload') . '"/></form></div>' .
                '<div class="phdr"><small>' . _t('File weight should not exceed') . ' ' . $config['flsz'] . 'kb<br>' .
                _t('Allowed extensions') . ': ' . implode(', ',
                    $al_ext) . ($set_down['screen_resize'] ? '<br>' . _t('A screenshot is automatically converted to a picture, of a width not exceeding 240px (height will be calculated automatically)') : '') . '</small></div>' .
                '<p><a href="?id=' . $id . '">' . _t('Back') . '</a></p>';
        }
    } else {
        echo _t('Access forbidden') . ' <a href="?id=' . $id . '">' . _t('Back') . '</a>';
    }
} else {
    echo _t('The directory does not exist') . '<a href="?">' . _t('Downloads') . '</a>';
    exit;
}

require_once('../system/end.php');
