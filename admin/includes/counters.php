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

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Проверяем права доступа
if ($systemUser->rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}

switch ($mod) {
    case 'view':
        // Предварительный просмотр счетчиков
        if ($id) {
            $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                if (isset($_GET['go']) && $_GET['go'] == 'on') {
                    $db->exec('UPDATE `cms_counters` SET `switch` = 1 WHERE `id` = ' . $id);
                    $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);
                } elseif (isset($_GET['go']) && $_GET['go'] == 'off') {
                    $db->exec('UPDATE `cms_counters` SET `switch` = 0 WHERE `id` = ' . $id);
                    $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);
                }

                $res = $req->fetch();
                echo '<div class="phdr"><a href="index.php?act=counters"><b>' . _t('Counters') . '</b></a> | ' . _t('Viewing') . '</div>';
                echo '<div class="menu">' . ($res['switch'] == 1 ? '<span class="green">[ON]</span>' : '<span class="red">[OFF]</span>') . '&#160;<b>' . $res['name'] . '</b></div>';
                echo ($res['switch'] == 1 ? '<div class="gmenu">' : '<div class="rmenu">') . '<p><h3>' . _t('Option 1') . '</h3>' . $res['link1'] . '</p>';
                echo '<p><h3>' . _t('Option 2') . '</h3>' . $res['link2'] . '</p>';
                echo '<p><h3>' . _t('Display mode') . '</h3>';

                switch ($res['mode']) {
                    case 2:
                        echo _t('On all pages showing option 1');
                        break;

                    case 3:
                        echo _t('On all pages showing option 2');
                        break;

                    default:
                        echo _t('On the main showing option 1, on the other pages option 2');
                }

                echo '</p></div>';
                echo '<div class="phdr">'
                    . ($res['switch'] == 1 ? '<a href="index.php?act=counters&amp;mod=view&amp;go=off&amp;id=' . $id . '">' . _t('Disable') . '</a>'
                        : '<a href="index.php?act=counters&amp;mod=view&amp;go=on&amp;id=' . $id . '">' . _t('Enable') . '</a>')
                    . ' | <a href="index.php?act=counters&amp;mod=edit&amp;id=' . $id . '">' . _t('Edit') . '</a> | <a href="index.php?act=counters&amp;mod=del&amp;id=' . $id . '">' . _t('Delete') . '</a></div>';
            } else {
                echo $tools->displayError(_t('Wrong data'));
            }
        }
        break;

    case 'up':
        // Перемещение счетчика на одну позицию вверх
        if ($id) {
            $req = $db->query('SELECT `sort` FROM `cms_counters` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();
                $sort = $res['sort'];
                $req = $db->query("SELECT * FROM `cms_counters` WHERE `sort` < '$sort' ORDER BY `sort` DESC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    $db->exec("UPDATE `cms_counters` SET `sort` = '$sort2' WHERE `id` = '$id'");
                    $db->exec("UPDATE `cms_counters` SET `sort` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }

        header('Location: index.php?act=counters');
        break;

    case 'down':
        // Перемещение счетчика на одну позицию вниз
        if ($id) {
            $req = $db->query('SELECT `sort` FROM `cms_counters` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();
                $sort = $res['sort'];
                $req = $db->query("SELECT * FROM `cms_counters` WHERE `sort` > '$sort' ORDER BY `sort` ASC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    $db->exec("UPDATE `cms_counters` SET `sort` = '$sort2' WHERE `id` = '$id'");
                    $db->exec("UPDATE `cms_counters` SET `sort` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=counters');
        break;

    case 'del':
        // Удаление счетчика
        if (!$id) {
            echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=counters">' . _t('Back') . '</a>');
            require('../system/end.php');
            exit;
        }

        $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);

        if ($req->rowCount()) {
            if (isset($_POST['submit'])) {
                $db->exec('DELETE FROM `cms_counters` WHERE `id` = ' . $id);
                echo '<p>' . _t('Counter deleted') . '<br><a href="index.php?act=counters">' . _t('Continue') . '</a></p>';
                require('../system/end.php');
                exit;
            } else {
                echo '<form action="index.php?act=counters&amp;mod=del&amp;id=' . $id . '" method="post">';
                echo '<div class="phdr"><a href="index.php?act=counters"><b>' . _t('Counters') . '</b></a> | ' . _t('Delete') . '</div>';
                $res = $req->fetch();
                echo '<div class="rmenu"><p><h3>' . $res['name'] . '</h3>' . _t('Do you really want to delete?') . '</p><p><input type="submit" value="' . _t('Delete') . '" name="submit" /></p></div>';
                echo '<div class="phdr"><a href="index.php?act=counters">' . _t('Cancel') . '</a></div></form>';
            }
        } else {
            echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=counters">' . _t('Back') . '</a>');
            require('../system/end.php');
            exit;
        }
        break;

    case 'edit':
        // Форма добавления счетчика
        if (isset($_POST['submit'])) {
            // Предварительный просмотр
            $name = isset($_POST['name']) ? mb_substr(trim($_POST['name']), 0, 25) : '';
            $link1 = isset($_POST['link1']) ? trim($_POST['link1']) : '';
            $link2 = isset($_POST['link2']) ? trim($_POST['link2']) : '';
            $mode = isset($_POST['mode']) ? intval($_POST['mode']) : 1;

            if (empty($name) || empty($link1)) {
                echo $tools->displayError(_t('The required fields are not filled'), '<a href="index.php?act=counters&amp;mod=edit' . ($id ? '&amp;id=' . $id : '') . '">' . _t('Back') . '</a>');
                require('../system/end.php');
                exit;
            }

            echo '<div class="phdr"><a href="index.php?act=counters"><b>' . _t('Counters') . '</b></a> | ' . _t('Preview') . '</div>' .
                '<div class="menu"><p><h3>' . _t('Title') . '</h3><b>' . htmlspecialchars($name) . '</b></p>' .
                '<p><h3>' . _t('Option 1') . '</h3>' . $link1 . '</p>' .
                '<p><h3>' . _t('Option 2') . '</h3>' . $link2 . '</p></div>' .
                '<div class="rmenu">' . _t('If the counter are displayed correctly and without errors, click &quot;Save&quot;.<br>Otherwise, click back button and correct errors.') . '</div>' .
                '<form action="index.php?act=counters&amp;mod=add" method="post">' .
                '<input type="hidden" value="' . $name . '" name="name" />' .
                '<input type="hidden" value="' . htmlspecialchars($link1) . '" name="link1" />' .
                '<input type="hidden" value="' . htmlspecialchars($link2) . '" name="link2" />' .
                '<input type="hidden" value="' . $mode . '" name="mode" />';

            if ($id) {
                echo '<input type="hidden" value="' . $id . '" name="id" />';
            }

            echo '<div class="bmenu"><input type="submit" value="' . _t('Save') . '" name="submit" /></div>';
            echo '</form>';
        } else {
            $name = '';
            $link1 = '';
            $link2 = '';
            $mode = 0;

            if ($id) {
                // запрос к базе, если счетчик редактируется
                $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $name = $res['name'];
                    $link1 = htmlspecialchars($res['link1']);
                    $link2 = htmlspecialchars($res['link2']);
                    $mode = $res['mode'];
                    $switch = 1;
                } else {
                    echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=counters">' . _t('Back') . '</a>');
                    require('../system/end.php');
                    exit;
                }
            }

            echo '<form action="index.php?act=counters&amp;mod=edit" method="post">' .
                '<div class="phdr"><a href="index.php?act=counters"><b>' . _t('Counters') . '</b></a> | ' . _t('Add') . '</div>' .
                '<div class="menu"><p><h3>' . _t('Title') . '</h3><input type="text" name="name" value="' . $name . '" /></p>' .
                '<p><h3>' . _t('Option 1') . '</h3><textarea rows="3" name="link1">' . $link1 . '</textarea><br><small>' . _t('Code for main page') . '</small></p>' .
                '<p><h3>' . _t('Option 2') . '</h3><textarea rows="3" name="link2">' . $link2 . '</textarea><br><small>' . _t('Code for other pages') . '</small></p>' .
                '<p><h3>' . _t('Display mode') . '</h3>' . '<input type="radio" value="1" ' . ($mode == 0 || $mode == 1 ? 'checked="checked" ' : '') . 'name="mode" />&#160;' . _t('Default') . '<br>' .
                '<small>' . _t('On the main showing option 1, on the other pages option 2.<br>If &quot;option 2&quot; not filled, counter would only appear on the main page.') . '</small></p><p>' .
                '<input type="radio" value="2" ' . ($mode == 2 ? 'checked="checked" ' : '') . 'name="mode" />&#160;' . _t('Option 1') . '<br>' .
                '<input type="radio" value="3" ' . ($mode == 3 ? 'checked="checked" ' : '') . 'name="mode" />&#160;' . _t('Option 2') . '</p></div>' .
                '<div class="rmenu"><small>' . _t('WARNING!<br>Make sure you have correctly entered the code. It must meet the standard of XML <br> If you click &quot;View&quot; and XHTML errors occured, then click &quot;Back&quot; button in your browser, return to this form and correct the errors.') . '</small></div>';

            if ($id) {
                echo '<input type="hidden" value="' . $id . '" name="id" />';
            }

            echo '<div class="bmenu"><input type="submit" value="' . _t('Viewing') . '" name="submit" /></div>';
            echo '</form>';
        }
        break;

    case 'add':
        // Запись счетчика в базу
        $name = isset($_POST['name']) ? mb_substr($_POST['name'], 0, 25) : '';
        $link1 = isset($_POST['link1']) ? $_POST['link1'] : '';
        $link2 = isset($_POST['link2']) ? $_POST['link2'] : '';
        $mode = isset($_POST['mode']) ? intval($_POST['mode']) : 1;

        if (empty($name) || empty($link1)) {
            echo $tools->displayError(_t('The required fields are not filled'), '<a href="index.php?act=counters&amp;mod=edit' . ($id ? '&amp;id=' . $id : '') . '">' . _t('Back') . '</a>');
            require_once('../system/end.php');
            exit;
        }

        if ($id) {
            // Режим редактирования
            $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);

            if (!$req->rowCount()) {
                echo $tools->displayError(_t('Wrong data'));
                require_once('../system/end.php');
                exit;
            }

            $db->prepare('
              UPDATE `cms_counters` SET
              `name` = ?,
              `link1` = ?,
              `link2` = ?,
              `mode` = ?
              WHERE `id` = ?
            ')->execute([
                $name,
                $link1,
                $link2,
                $mode,
                $id,
            ]);
        } else {
            // Получаем значение сортировки
            $req = $db->query('SELECT `sort` FROM `cms_counters` ORDER BY `sort` DESC LIMIT 1');

            if ($req->rowCount()) {
                $res = $req->fetch();
                $sort = $res['sort'] + 1;
            } else {
                $sort = 1;
            }

            // Режим добавления
            $db->prepare('
              INSERT INTO `cms_counters` SET
              `name` = ?,
              `sort` = ?,
              `link1` = ?,
              `link2` = ?,
              `mode` = ?
            ')->execute([
                $name,
                $sort,
                $link1,
                $link2,
                $mode,
            ]);
        }

        echo '<div class="gmenu"><p>' . ($id ? _t('Counter successfully changed') : _t('Counter successfully added')) . '</p></div>';
        break;

    default:
        // Вывод списка счетчиков
        echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Counters') . '</div>';
        $req = $db->query('SELECT * FROM `cms_counters` ORDER BY `sort` ASC');

        if ($req->rowCount()) {
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<img src="../images/' . ($res['switch'] == 1 ? 'green' : 'red') . '.gif" width="16" height="16" class="left"/>&#160;';
                echo '<a href="index.php?act=counters&amp;mod=view&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a><br>';
                echo '<div class="sub"><a href="index.php?act=counters&amp;mod=up&amp;id=' . $res['id'] . '">' . _t('Up') . '</a> | ';
                echo '<a href="index.php?act=counters&amp;mod=down&amp;id=' . $res['id'] . '">' . _t('Down') . '</a> | ';
                echo '<a href="index.php?act=counters&amp;mod=edit&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a> | ';
                echo '<a href="index.php?act=counters&amp;mod=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a></div></div>';
                ++$i;
            }
        }

        echo '<div class="phdr"><a href="index.php?act=counters&amp;mod=edit">' . _t('Add') . '</a></div>';
}

echo '<p>' . ($mod ? '<a href="index.php?act=counters">' . _t('Counters') . '</a><br>' : '') . '<a href="index.php">' . _t('Admin Panel') . '</a></p>';
