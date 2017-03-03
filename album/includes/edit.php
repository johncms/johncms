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

require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Создать / изменить альбом
if ($user['id'] == $systemUser->id && empty($systemUser->ban) || $systemUser->rights >= 7) {
    if ($al) {
        $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '$al' AND `user_id` = " . $user['id']);

        if ($req->rowCount()) {
            echo '<div class="phdr"><b>' . _t('Edit Album') . '</b></div>';
            $res = $req->fetch();
            $name = htmlspecialchars($res['name']);
            $description = htmlspecialchars($res['description']);
            $password = htmlspecialchars($res['password']);
            $access = $res['access'];
        } else {
            echo $tools->displayError(_t('Wrong data'));
            require('../system/end.php');
            exit;
        }
    } else {
        echo '<div class="phdr"><b>' . _t('Create Album') . '</b></div>';
        $name = '';
        $description = '';
        $password = '';
        $access = 0;
    }

    $error = [];

    if (isset($_POST['submit'])) {
        // Принимаем данные
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $access = isset($_POST['access']) ? abs(intval($_POST['access'])) : null;

        // Проверяем на ошибки
        if (empty($name)) {
            $error[] = _t('You have not entered Title');
        } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            $error[] = _t('Title') . ': ' . _t('Invalid length');
        }

        $description = mb_substr($description, 0, 500);

        if ($access == 2 && empty($password)) {
            $error[] = _t('You have not entered password');
        } elseif ($access == 2 && mb_strlen($password) < 3 || mb_strlen($password) > 15) {
            $error[] = _t('Password') . ': ' . _t('Invalid length');
        }

        if ($access < 1 || $access > 4) {
            $error[] = _t('Wrong data');
        }

        // Проверяем, есть ли уже альбом с таким же именем?
        if (!$al && $db->query("SELECT * FROM `cms_album_cat` WHERE `name` = " . $db->quote($name) . " AND `user_id` = '" . $user['id'] . "' LIMIT 1")->rowCount()) {
            $error[] = _t('The album already exists');
        }

        if (!$error) {
            if ($al) {
                // Изменяем данные в базе
                $db->exec("UPDATE `cms_album_files` SET `access` = '$access' WHERE `album_id` = '$al' AND `user_id` = " . $user['id']);
                $db->prepare('
                  UPDATE `cms_album_cat` SET
                  `name` = ?,
                  `description` = ?,
                  `password` = ?,
                  `access` = ?
                  WHERE `id` = ? AND `user_id` = ?
                ')->execute([
                    $name,
                    $description,
                    $password,
                    $access,
                    $al,
                    $user['id'],
                ]);
            } else {
                // Вычисляем сортировку
                $req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $user['id'] . "' ORDER BY `sort` DESC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $sort = $res['sort'] + 1;
                } else {
                    $sort = 1;
                }

                // Заносим данные в базу
                $db->prepare('
                  INSERT INTO `cms_album_cat` SET
                  `user_id` = ?,
                  `name` = ?,
                  `description` = ?,
                  `password` = ?,
                  `access` = ?,
                  `sort` = ?
                ')->execute([
                    $user['id'],
                    $name,
                    $description,
                    $password,
                    $access,
                    $sort,
                ]);
            }

            echo '<div class="gmenu"><p>' . ($al ? _t('Album successfully changed') : _t('Album successfully created')) . '<br>' .
                '<a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Continue') . '</a></p></div>';
            require('../system/end.php');
            exit;
        }
    }

    if ($error) {
        echo $tools->displayError($error);
    }

    echo '<div class="menu">' .
        '<form action="?act=edit&amp;user=' . $user['id'] . '&amp;al=' . $al . '" method="post">' .
        '<p><h3>' . _t('Title') . '</h3>' .
        '<input type="text" name="name" value="' . $tools->checkout($name) . '" maxlength="30" /><br>' .
        '<small>' . _t('Min. 2, Max. 30') . '</small></p>' .
        '<p><h3>' . _t('Description') . '</h3>' .
        '<textarea name="description" rows="' . $systemUser->getConfig()->fieldHeight . '">' . $tools->checkout($description) . '</textarea><br>' .
        '<small>' . _t('Optional field') . '. ' . _t('Max. 500') . '</small></p>' .
        '<p><h3>' . _t('Password') . '</h3>' .
        '<input type="text" name="password" value="' . $tools->checkout($password) . '" maxlength="15" /><br>' .
        '<small>' . _t('This field is required if the password access is activated') . '<br>Min. 3, Max. 15</small></p>' .
        '<p><h3>' . _t('Access') . '</h3>' .
        '<input type="radio" name="access" value="4" ' . (!$access || $access == 4 ? 'checked="checked"' : '') . '/>&#160;' . _t('Everyone') . '<br>' .
        '<input type="radio" name="access" value="2" ' . ($access == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('With Password') . '<br>' .
        '<input type="radio" name="access" value="1" ' . ($access == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for me') . '</p>' .
        '<p><input type="submit" name="submit" value="' . _t('Save') . '" /></p>' .
        '</form></div>' .
        '<div class="phdr"><a href="?act=list&amp;user=' . $user['id'] . '">' . _t('Cancel') . '</a></div>';
}
