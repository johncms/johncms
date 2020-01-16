<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$data = [];
$title = __('Counters');
$nav_chain->add($title);

if ($user->rights < 9) {
    echo $view->render(
        'system::pages/result',
        [
            'title'       => $title,
            'type'        => 'alert-danger',
            'message'     => __('Access denied'),
            'admin'       => true,
            'menu_item'   => 'counters',
            'parent_menu' => 'module_menu',
        ]
    );
    exit;
}

switch ($mod) {
    case 'view':
        // Предварительный просмотр счетчиков
        if ($id) {
            $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);
            $title = __('Viewing');
            if ($req->rowCount()) {
                if (isset($_GET['go']) && $_GET['go'] == 'on') {
                    $db->exec('UPDATE `cms_counters` SET `switch` = 1 WHERE `id` = ' . $id);
                    $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);
                } elseif (isset($_GET['go']) && $_GET['go'] == 'off') {
                    $db->exec('UPDATE `cms_counters` SET `switch` = 0 WHERE `id` = ' . $id);
                    $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);
                }

                $res = $req->fetch();

                switch ($res['mode']) {
                    case 2:
                        $data['mode_name'] = __('On all pages showing option 1');
                        break;

                    case 3:
                        $data['mode_name'] = __('On all pages showing option 2');
                        break;

                    default:
                        $data['mode_name'] = __('On the main showing option 1, on the other pages option 2');
                }

                $data['name'] = htmlspecialchars($res['name']);
                $data['counter_1'] = $res['link1'] ?? '';
                $data['counter_1_safe'] = htmlspecialchars($res['link1']) ?? '';
                $data['counter_2'] = $res['link2'] ?? '';
                $data['counter_2_safe'] = htmlspecialchars($res['link2']) ?? '';
                $data['id'] = $id ?? 0;
                $data['enabled'] = $res['switch'] === 1;
                echo $view->render(
                    'admin::counters_view',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                        'data'       => $data,
                    ]
                );
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'       => $title,
                        'type'        => 'alert-danger',
                        'message'     => __('Wrong data'),
                        'admin'       => true,
                        'menu_item'   => 'counters',
                        'parent_menu' => 'module_menu',
                        'back_url'    => '?',
                    ]
                );
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
                $req = $db->query("SELECT * FROM `cms_counters` WHERE `sort` < '${sort}' ORDER BY `sort` DESC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    $db->exec("UPDATE `cms_counters` SET `sort` = '${sort2}' WHERE `id` = '${id}'");
                    $db->exec("UPDATE `cms_counters` SET `sort` = '${sort}' WHERE `id` = '${id2}'");
                }
            }
        }

        header('Location: ?act=counters');
        break;

    case 'down':
        // Перемещение счетчика на одну позицию вниз
        if ($id) {
            $req = $db->query('SELECT `sort` FROM `cms_counters` WHERE `id` = ' . $id);

            if ($req->rowCount()) {
                $res = $req->fetch();
                $sort = $res['sort'];
                $req = $db->query("SELECT * FROM `cms_counters` WHERE `sort` > '${sort}' ORDER BY `sort` ASC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    $db->exec("UPDATE `cms_counters` SET `sort` = '${sort2}' WHERE `id` = '${id}'");
                    $db->exec("UPDATE `cms_counters` SET `sort` = '${sort}' WHERE `id` = '${id2}'");
                }
            }
        }
        header('Location: ?act=counters');
        break;

    case 'del':
        // Удаление счетчика
        if (! $id) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'       => $title,
                    'type'        => 'alert-danger',
                    'message'     => __('Wrong data'),
                    'admin'       => true,
                    'menu_item'   => 'counters',
                    'parent_menu' => 'module_menu',
                    'back_url'    => '?',
                ]
            );
            exit;
        }

        $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);

        if ($req->rowCount()) {
            if (isset($_POST['submit'])) {
                $db->exec('DELETE FROM `cms_counters` WHERE `id` = ' . $id);
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('Counter deleted'),
                        'admin'         => true,
                        'menu_item'     => 'counters',
                        'parent_menu'   => 'module_menu',
                        'back_url'      => '?',
                        'back_url_name' => __('Continue'),
                    ]
                );
                exit;
            }
            $res = $req->fetch();
            $title = __('Delete:') . ' ' . htmlspecialchars($res['name']);
            $data['message'] = __('Do you really want to delete?');
            $data['form_action'] = '?mod=del&amp;id=' . $id;
            $data['back_url'] = '?act=ipban&amp;mod=new';
            echo $view->render(
                'admin::counters_confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'       => $title,
                    'type'        => 'alert-danger',
                    'message'     => __('Wrong data'),
                    'admin'       => true,
                    'menu_item'   => 'counters',
                    'parent_menu' => 'module_menu',
                    'back_url'    => '?',
                ]
            );
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
            $mode = isset($_POST['mode']) ? (int) ($_POST['mode']) : 1;

            if (empty($name) || empty($link1)) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'       => $title,
                        'type'        => 'alert-danger',
                        'message'     => __('The required fields are not filled'),
                        'admin'       => true,
                        'menu_item'   => 'counters',
                        'parent_menu' => 'module_menu',
                        'back_url'    => '?mod=edit' . ($id ? '&amp;id=' . $id : ''),
                    ]
                );
                exit;
            }

            $data['name'] = htmlspecialchars($name) ?? '';
            $data['counter_1'] = $link1 ?? '';
            $data['counter_1_safe'] = htmlspecialchars($link1) ?? '';
            $data['counter_2'] = $link2 ?? '';
            $data['counter_2_safe'] = htmlspecialchars($link2) ?? '';
            $data['id'] = $id ?? 0;
            $data['form_action'] = '?mod=add';
            $data['mode'] = $mode;
            echo $view->render(
                'admin::counters_add_confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
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
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'       => $title,
                            'type'        => 'alert-danger',
                            'message'     => __('Wrong data'),
                            'admin'       => true,
                            'menu_item'   => 'counters',
                            'parent_menu' => 'module_menu',
                            'back_url'    => '?',
                        ]
                    );
                    exit;
                }
            }

            $data['name'] = htmlspecialchars($name) ?? '';
            $data['counter_1'] = $link1 ?? '';
            $data['counter_2'] = $link2 ?? '';
            $data['id'] = $id ?? 0;
            $data['form_action'] = '?mod=edit';
            $data['mode'] = $mode;
            echo $view->render(
                'admin::counters_form',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        break;

    case 'add':
        // Запись счетчика в базу
        $name = isset($_POST['name']) ? mb_substr($_POST['name'], 0, 25) : '';
        $link1 = $_POST['link1'] ?? '';
        $link2 = $_POST['link2'] ?? '';
        $mode = isset($_POST['mode']) ? (int) ($_POST['mode']) : 1;

        if (empty($name) || empty($link1)) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'       => $title,
                    'type'        => 'alert-danger',
                    'message'     => __('The required fields are not filled'),
                    'admin'       => true,
                    'menu_item'   => 'counters',
                    'parent_menu' => 'module_menu',
                    'back_url'    => '?mod=edit' . ($id ? '&amp;id=' . $id : ''),
                ]
            );
            exit;
        }

        if ($id) {
            // Режим редактирования
            $req = $db->query('SELECT * FROM `cms_counters` WHERE `id` = ' . $id);

            if (! $req->rowCount()) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'       => $title,
                        'type'        => 'alert-danger',
                        'message'     => __('Wrong data'),
                        'admin'       => true,
                        'menu_item'   => 'counters',
                        'parent_menu' => 'module_menu',
                        'back_url'    => '?',
                    ]
                );
                exit;
            }

            $db->prepare(
                '
              UPDATE `cms_counters` SET
              `name` = ?,
              `link1` = ?,
              `link2` = ?,
              `mode` = ?
              WHERE `id` = ?
            '
            )->execute(
                [
                    $name,
                    $link1,
                    $link2,
                    $mode,
                    $id,
                ]
            );
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
            $db->prepare(
                '
              INSERT INTO `cms_counters` SET
              `name` = ?,
              `sort` = ?,
              `link1` = ?,
              `link2` = ?,
              `mode` = ?
            '
            )->execute(
                [
                    $name,
                    $sort,
                    $link1,
                    $link2,
                    $mode,
                ]
            );
        }
        echo $view->render(
            'system::pages/result',
            [
                'title'       => $title,
                'type'        => 'alert-success',
                'message'     => ($id ? __('Counter successfully changed') : __('Counter successfully added')),
                'admin'       => true,
                'menu_item'   => 'counters',
                'parent_menu' => 'module_menu',
                'back_url'    => '?',
            ]
        );
        break;

    default:
        // Вывод списка счетчиков
        $req = $db->query('SELECT * FROM `cms_counters` ORDER BY `sort` ASC');
        $total = $req->rowCount();
        if ($total > 0) {
            $items = [];
            while ($res = $req->fetch()) {
                $items[] = $res;
            }
        }

        $data['items'] = $items ?? [];
        $data['total'] = $total;
        $data['back_url'] = '/admin/';
        echo $view->render(
            'admin::counters',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
