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

// Редактирование категорий
if ($systemUser->rights == 4 || $systemUser->rights >= 6) {
    require_once('../system/head.php');

    $req = $db->query("SELECT * FROM `download__category` WHERE `id` = " . $id);
    $res = $req->fetch();

    if (!$req->rowCount() || !is_dir($res['dir'])) {
        echo _t('The directory does not exist') . ' <a href="?">' . _t('Downloads') . '</a>';
        exit;
    }

    // Сдвиг категорий
    if (isset($_GET['up']) || isset($_GET['down'])) {
        if (isset($_GET['up'])) {
            $order = 'DESC';
            $val = '<';
        } else {
            $order = 'ASC';
            $val = '>';
        }

        $req_two = $db->query("SELECT * FROM `download__category` WHERE `refid` = '" . $res['refid'] . "' AND `sort` $val '" . $res['sort'] . "' ORDER BY `sort` $order LIMIT 1");

        if ($req_two->rowCount()) {
            $res_two = $req_two->fetch();
            $db->exec("UPDATE `download__category` SET `sort` = '" . $res_two['sort'] . "' WHERE `id` = '" . $id . "' LIMIT 1");
            $db->exec("UPDATE `download__category` SET `sort` = '" . $res['sort'] . "' WHERE `id` = '" . $res_two['id'] . "' LIMIT 1");
        }

        header('location: ?id=' . $res['refid']);
        exit;
    }

    // Изменяем данные
    if (isset($_POST['submit'])) {
        $rus_name = isset($_POST['rus_name']) ? trim($_POST['rus_name']) : '';

        if (empty($rus_name)) {
            $error[] = _t('The required fields are not filled');
        }

        $error_format = false;

        if ($systemUser->rights == 9 && isset($_POST['user_down'])) {
            $format = isset($_POST['format']) ? trim($_POST['format']) : false;
            $format_array = explode(', ', $format);
            foreach ($format_array as $value) {
                if (!in_array($value, $defaultExt)) {
                    $error_format .= 1;
                }
            }
            $user_down = 1;
            $format_files = htmlspecialchars($format);
        } else {
            $user_down = 0;
            $format_files = '';
        }

        if ($error_format) {
            $error[] = _t('You can write only the following extensions') . ': ' . implode(', ', $defaultExt);
        }

        if ($error) {
            echo $error . ' <a href="?act=edit_cat&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
            exit;
        }

        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';

        $stmt = $db->prepare("
            UPDATE `download__category` SET
            `field`    = ?,
            `text`     = ?,
            `desc`     = ?,
            `rus_name` = ?
            WHERE `id` = ?
        ");

        $stmt->execute([
            $user_down,
            $format_files,
            $desc,
            $rus_name,
            $id,
        ]);

        header('location: ?id=' . $id);
    } else {
        $name = htmlspecialchars($res['rus_name']);
        echo '<div class="phdr"><b>' . _t('Change Folder') . ':</b> ' . $name . '</div>' .
            '<div class="menu"><form action="?act=folder_edit&amp;id=' . $id . '" method="post">' .
            _t('Title to display') . ':<br><input type="text" name="rus_name" value="' . $name . '"/><br>' .
            _t('Description') . ' (max. 500):<br><textarea name="desc" rows="4">' . htmlspecialchars($res['desc']) . '</textarea><br>';

        if ($systemUser->rights == 9) {
            echo '<div class="sub"><input type="checkbox" name="user_down" value="1"' . ($res['field'] ? ' checked="checked"' : '') . '/> ' . _t('Allow users to upload files') . '<br>' .
                _t('Allowed extensions') . ':<br><input type="text" name="format" value="' . $res['text'] . '"/></div>' .
                '<div class="sub">' . _t('You can write only the following extensions') . ':<br> ' . implode(', ', $defaultExt) . '</div>';
        }

        echo '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></form></div>';
    }

    echo '<div class="phdr"><a href="?id=' . $id . '">' . _t('Back') . '</a></div>';
    require_once('../system/end.php');
}
