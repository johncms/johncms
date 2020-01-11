<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

switch ($mod) {
    case 'edit':
        // Добавляем / редактируем ссылку
        echo '<div class="phdr"><a href="?act=ads"><b>' . __('Advertisement') . '</b></a> | ' . ($id ? __('Edit link') : __('Add link')) . '</div>';

        if ($id) {
            // Если ссылка редактироется, запрашиваем ее данные в базе
            $req = $db->query('SELECT * FROM `cms_ads` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();
            } else {
                echo $tools->displayError(__('Wrong data'), '<a href="?act=ads">' . __('Back') . '</a>');
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }
        } else {
            $res = ['link' => 'http://'];
        }

        if (isset($_POST['submit'])) {
            $link = isset($_POST['link']) ? trim($_POST['link']) : '';
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $bold = isset($_POST['bold']) ? 1 : 0;
            $italic = isset($_POST['italic']) ? 1 : 0;
            $underline = isset($_POST['underline']) ? 1 : 0;
            $show = isset($_POST['show']) ? 1 : 0;
            $view = isset($_POST['view']) ? abs((int) ($_POST['view'])) : 0;
            $day = isset($_POST['day']) ? abs((int) ($_POST['day'])) : 0;
            $count = isset($_POST['count']) ? abs((int) ($_POST['count'])) : 0;
            $day = isset($_POST['day']) ? abs((int) ($_POST['day'])) : 0;
            $layout = isset($_POST['layout']) ? abs((int) ($_POST['layout'])) : 0;
            $type = isset($_POST['type']) ? (int) ($_POST['type']) : 0;
            $mesto = isset($_POST['mesto']) ? abs((int) ($_POST['mesto'])) : 0;
            $color = isset($_POST['color']) ? mb_substr(trim($_POST['color']), 0, 6) : '';
            $error = [];

            if (empty($link) || empty($name)) {
                $error[] = __('The required fields are not filled');
            }

            if ($type > 3 || $type < 0) {
                $type = 0;
            }

            if (! $mesto) {
                $total = $db->query("SELECT COUNT(*) FROM `cms_ads` WHERE `mesto` = '" . $mesto . "' AND `type` = '" . $type . "'")->fetchColumn();

                if ($total) {
                    $error[] = __('This place is occupied');
                }
            }

            if ($color) {
                if (preg_match("/[^\da-fA-F_]+/", $color)) {
                    $error[] = __('Invalid characters');
                }
                if (strlen($color) < 6) {
                    $error[] = __('Color is specified incorrectly');
                }
            }

            if ($error) {
                echo $tools->displayError($error, '<a href="?act=ads&amp;from=addlink">' . __('Back') . '</a>');
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            if ($id) {
                // Обновляем ссылку после редактирования
                $db->prepare(
                    '
                  UPDATE `cms_ads` SET
                  `type` = ?,
                  `view` = ?,
                  `link` = ?,
                  `name` = ?,
                  `color` = ?,
                  `count_link` = ?,
                  `day` = ?,
                  `layout` = ?,
                  `show` = ?,
                  `bold` = ?,
                  `italic` = ?,
                  `underline` = ?
                  WHERE `id` = ?
                '
                )->execute(
                    [
                        $type,
                        $view,
                        $link,
                        $name,
                        $color,
                        $count,
                        $day,
                        $layout,
                        $show,
                        $bold,
                        $italic,
                        $underline,
                        $id,
                    ]
                );
            } else {
                // Добавляем новую ссылку
                $req = $db->query('SELECT `mesto` FROM `cms_ads` ORDER BY `mesto` DESC LIMIT 1');

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $mesto = $res['mesto'] + 1;
                } else {
                    $mesto = 1;
                }

                $db->prepare(
                    '
                  INSERT INTO `cms_ads` SET
                  `type` = ?,
                  `view` = ?,
                  `mesto` = ?,
                  `link` = ?,
                  `name` = ?,
                  `color` = ?,
                  `count_link` = ?,
                  `day` = ?,
                  `layout` = ?,
                  `show` = ?,
                  `time` = ?,
                  `to` = 0,
                  `bold` = ?,
                  `italic` = ?,
                  `underline` = ?
                '
                )->execute(
                    [
                        $type,
                        $view,
                        $mesto,
                        $link,
                        $name,
                        $color,
                        $count,
                        $day,
                        $layout,
                        $show,
                        time(),
                        $bold,
                        $italic,
                        $underline,
                    ]
                );
            }

            echo '<div class="menu"><p>' . ($id ? __('Link successfully changed') : __('Link successfully added')) . '<br>' .
                '<a href="?act=ads&amp;sort=' . $type . '">' . __('Continue') . '</a></p></div>';
        } else {
            // Форма добавления / изменения ссылки
            echo '<form action="?act=ads&amp;mod=edit' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
                '<div class="menu"><p><h3>' . __('Link') . '</h3>' .
                '<input type="text" name="link" value="' . htmlentities($res['link'], ENT_QUOTES, 'UTF-8') . '"/><br>' .
                '<input type="checkbox" name="show" ' . ($res['show'] ? 'checked="checked"' : '') . '/>&nbsp;' . __('Direct Link') . '<br>' .
                '<small>' . __('Click statistics won\'t be counted, If the direct link is turned on') . '</small></p>' .
                '<p><h3>' . __('Title') . '</h3>' .
                '<input type="text" name="name" value="' . htmlentities(
                    (string) $res['name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) . '"/><br>' .
                '<small>' . __('To change the name when updating pages, you must wtite names trought the symbol |') . '</small></p>' .
                '<p><h3>' . __('Color') . '</h3>' .
                '<input type="text" name="color" size="6" value="' . $res['color'] . '"/><br>' .
                '<small>' . __('In the format FFFFFF, if you do not want to use link color, simply do not fill this field') . '</small></p>' .
                '<p><h3>' . __('Hits') . '</h3>' .
                '<input type="text" name="count" size="6" value="' . $res['count_link'] . '"/><br>' .
                '<small>' . __('Number of hits for link existence which will be automatically removed from the page<br>0 - Unlimited') . '</small></p>' .
                '<p><h3>' . __('Days') . '</h3>' .
                '<input type="text" name="day" size="6" value="' . $res['day'] . '"/><br>' .
                '<small>' . __('Number of days for link existence which will be automatically removed from the page<br>0 - Unlimited') . '</small></p>' .
                '</div><div class="gmenu">' .
                '<p><h3>' . __('Show') . '</h3>' .
                '<input type="radio" name="view" value="0" ' . (! $res['view'] ? 'checked="checked"' : '') . '/>&nbsp;' . __('Everyone') . '<br>' .
                '<input type="radio" name="view" value="1" ' . ($res['view'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;' . __('Guests') . '<br>' .
                '<input type="radio" name="view" value="2" ' . ($res['view'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;' . __('Users') . '</p>' .
                '<p><h3>' . __('Location') . '</h3>' .
                '<input type="radio" name="type" value="0" ' . (! $res['type'] ? 'checked="checked"' : '') . '/>&nbsp;' . __('Above logo') . '<br>' .
                '<input type="radio" name="type" value="1" ' . ($res['type'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;' . __('Under menu') . '<br>' .
                '<input type="radio" name="type" value="2" ' . ($res['type'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;' . __('Over the counter') . '<br>' .
                '<input type="radio" name="type" value="3" ' . ($res['type'] == 3 ? 'checked="checked"' : '') . '/>&nbsp;' . __('Under counter') . '</p>' .
                '<p><h3>' . __('Layout') . '</h3>' .
                '<input type="radio" name="layout" value="0" ' . (! $res['layout'] ? 'checked="checked"' : '') . '/>&nbsp;' . __('All pages') . '<br>' .
                '<input type="radio" name="layout" value="1" ' . ($res['layout'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;' . __('Only on Homepage') . '<br>' .
                '<input type="radio" name="layout" value="2" ' . ($res['layout'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;' . __('On all, except Homepage') . '</p>' .
                '<p><h3>' . __('Styling links') . '</h3>' .
                '<input type="checkbox" name="bold" ' . ($res['bold'] ? 'checked="checked"' : '') . '/>&nbsp;<b>' . __('Bold') . '</b><br>' .
                '<input type="checkbox" name="italic" ' . ($res['italic'] ? 'checked="checked"' : '') . '/>&nbsp;<i>' . __('Italic') . '</i><br>' .
                '<input type="checkbox" name="underline" ' . ($res['underline'] ? 'checked="checked"' : '') . '/>&nbsp;<u>' . __('Underline') . '</u></p></div>' .
                '<div class="phdr"><input type="submit" name="submit" value="' . ($id ? __('Edit') : __('Add')) . '" /></div></form>' .
                '<p><a href="?act=ads">' . __('Advertisement') . '</a><br>' .
                '<a href="./">' . __('Admin Panel') . '</a></p>';
        }
        break;

    case 'down':
        // Перемещаем на позицию вниз
        if ($id) {
            $req = $db->query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $mesto = $res['mesto'];

                $req = $db->query("SELECT * FROM `cms_ads` WHERE `mesto` > '${mesto}' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` ASC");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto2}' WHERE `id` = '${id}'");
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto}' WHERE `id` = '${id2}'");
                }
            }
        }
        header('Location: ' . getenv('HTTP_REFERER'));
        break;

    case 'up':
        // Перемещаем на позицию вверх
        if ($id) {
            $req = $db->query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $mesto = $res['mesto'];

                $req = $db->query("SELECT * FROM `cms_ads` WHERE `mesto` < '${mesto}' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` DESC");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto2}' WHERE `id` = '${id}'");
                    $db->exec("UPDATE `cms_ads` SET `mesto` = '${mesto}' WHERE `id` = '${id2}'");
                }
            }
        }
        header('Location: ' . getenv('HTTP_REFERER') . '');
        break;

    case 'del':
        // Удаляем ссылку
        if ($id) {
            if (isset($_POST['submit'])) {
                $db->exec("DELETE FROM `cms_ads` WHERE `id` = '${id}'");
                header('Location: ' . $_POST['ref']);
            } else {
                echo '<div class="phdr"><a href="?act=ads"><b>' . __('Advertisement') . '</b></a> | ' . __('Delete') . '</div>' .
                    '<div class="rmenu"><form action="?act=ads&amp;mod=del&amp;id=' . $id . '" method="post">' .
                    '<p>' . __('Are you sure want to delete link?') . '</p>' .
                    '<p><input type="submit" name="submit" value="' . __('Delete') . '" /></p>' .
                    '<input type="hidden" name="ref" value="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '" />' .
                    '</form></div>' .
                    '<div class="phdr"><a href="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '">' . __('Cancel') . '</a></div>';
            }
        }
        break;

    case 'clear':
        // Очистка базы от неактивных ссылок
        if (isset($_POST['submit'])) {
            $db->exec("DELETE FROM `cms_ads` WHERE `to` = '1'");
            $db->query('OPTIMIZE TABLE `cms_ads`');
            header('location: ?act=ads');
        } else {
            echo '<div class="phdr"><a href="?act=ads"><b>' . __('Advertisement') . '</b></a> | ' . __('Delete inactive links') . '</div>' .
                '<div class="menu"><form method="post" action="?act=ads&amp;mod=clear">' .
                '<p>' . __('Are you sure you want to delete all inactive links?') . '</p>' .
                '<p><input type="submit" name="submit" value="' . __('Delete') . '" />' .
                '</p></form></div>' .
                '<div class="phdr"><a href="?act=ads">' . __('Cancel') . '</a></div>';
        }
        break;

    case 'show':
        // Восстанавливаем / скрываем ссылку
        if ($id) {
            $req = $db->query("SELECT * FROM `cms_ads` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $db->exec("UPDATE `cms_ads` SET `to`='" . ($res['to'] ? 0 : 1) . "' WHERE `id` = '${id}'");
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        break;

    default:
        // Главное меню модуля управления рекламой
        echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Advertisement') . '</div>';
        $array_placing = [
            __('All pages'),
            __('Only on Homepage'),
            __('On all, except Homepage'),
        ];
        $array_show = [
            __('Everyone'),
            __('Guests'),
            __('Users'),
        ];
        $type = isset($_GET['type']) ? (int) ($_GET['type']) : 0;
        $array_menu = [
            (! $type ? __('Above logo') : '<a href="?act=ads">' . __('Above logo') . '</a>'),
            ($type == 1 ? __('Under menu') : '<a href="?act=ads&amp;type=1">' . __('Under menu') . '</a>'),
            ($type == 2 ? __('Over the counter') : '<a href="?act=ads&amp;type=2">' . __('Over the counter') . '</a>'),
            ($type == 3 ? __('Under counter') : '<a href="?act=ads&amp;type=3">' . __('Under counter') . '</a>'),
        ];
        echo '<div class="topmenu">' . implode(' | ', $array_menu) . '</div>';

        $total = $db->query("SELECT COUNT(*) FROM `cms_ads` WHERE `type` = '${type}'")->fetchColumn();

        if ($total) {
            $req = $db->query("SELECT * FROM `cms_ads` WHERE `type` = '${type}' ORDER BY `mesto` ASC LIMIT " . $start . ',' . $user->config->kmess);
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $name = str_replace('|', '; ', $res['name']);
                $name = htmlentities($name, ENT_QUOTES, 'UTF-8');

                // Если был задан цвет, то применяем
                if (! empty($res['color'])) {
                    $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
                }

                // Если было задано начертание шрифта, то применяем
                $font = $res['bold'] ? 'font-weight: bold;' : false;
                $font .= $res['italic'] ? ' font-style:italic;' : false;
                $font .= $res['underline'] ? ' text-decoration:underline;' : false;

                if ($font) {
                    $name = '<span style="' . $font . '">' . $name . '</span>';
                }

                // Выводим рекламмную ссылку с атрибутами
                echo '<p><img src="../images/' . ($res['to'] ? 'red' : 'green') . '.gif" width="16" height="16" class="left"/>&#160;' .
                    '<a href="' . htmlspecialchars($res['link']) . '">' . htmlspecialchars($res['link']) . '</a>&nbsp;[' . $res['count'] . ']<br>' . $name . '</p>';
                $menu = [
                    '<a href="?act=ads&amp;mod=up&amp;id=' . $res['id'] . '">' . __('Up') . '</a>',
                    '<a href="?act=ads&amp;mod=down&amp;id=' . $res['id'] . '">' . __('Down') . '</a>',
                    '<a href="?act=ads&amp;mod=edit&amp;id=' . $res['id'] . '">' . __('Edit') . '</a>',
                    '<a href="?act=ads&amp;mod=del&amp;id=' . $res['id'] . '">' . __('Delete') . '</a>',
                    '<a href="?act=ads&amp;mod=show&amp;id=' . $res['id'] . '">' . ($res['to'] ? __('Show') : __('Hide')) . '</a>',
                ];
                echo '<div class="sub">' .
                    '<div>' . implode(' | ', $menu) . '</div>' .
                    '<p><span class="gray">' . __('Start date') . ':</span> ' . $tools->displayDate($res['time']) . '<br>' .
                    '<span class="gray">' . __('Disposition') . ':</span>&nbsp;' . $array_placing[$res['layout']] . '<br>' .
                    '<span class="gray">' . __('Show') . ':</span>&nbsp;' . $array_show[$res['view']];
                // Вычисляем условия договора на рекламу
                $agreement = [];
                $remains = [];

                if (! empty($res['count_link'])) {
                    $agreement[] = $res['count_link'] . ' ' . __('hits');
                    $remains_count = $res['count_link'] - $res['count'];
                    if ($remains_count > 0) {
                        $remains[] = $remains_count . ' ' . __('hits');
                    }
                }

                if (! empty($res['day'])) {
                    $agreement[] = $tools->timecount($res['day'] * 86400);
                    $remains_count = $res['day'] * 86400 - (time() - $res['time']);
                    if ($remains_count > 0) {
                        $remains[] = $tools->timecount($remains_count);
                    }
                }

                // Если был договор, то выводим описание
                if ($agreement) {
                    echo '<br><span class="gray">' . __('Agreement') . ':</span>&nbsp;' . implode(', ', $agreement);

                    if ($remains) {
                        echo '<br><span class="gray">' . __('Remains') . ':</span> ' . implode(', ', $remains);
                    }
                }
                echo($res['show'] ? '<br><span class="red"><b>' . __('Direct Link') . '</b></span>' : '') . '</p></div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
        }

        echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' .
                $tools->displayPagination(
                    '?act=ads&amp;type=' . $type . '&amp;',
                    $start,
                    $total,
                    $user->config->kmess
                ) .
                '</div><p><form action="?act=ads&amp;type=' . $type . '" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . __('To Page') . ' &gt;&gt;"/></form></p>';
        }

        echo '<p><a href="?act=ads&amp;mod=edit">' . __('Add link') . '</a><br>' .
            '<a href="?act=ads&amp;mod=clear">' . __('Delete inactive links') . '</a><br>' .
            '<a href="./">' . __('Admin Panel') . '</a></p>';
}

echo $view->render(
    'system::app/old_content',
    [
        'title'   => __('Admin Panel'),
        'content' => ob_get_clean(),
    ]
);
