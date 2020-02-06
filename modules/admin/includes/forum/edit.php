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
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */

$title = __('Edit Section');
$nav_chain->add($title);

// Редактирование выбранной категории, или раздела
if (! $id) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => $title,
            'type'          => 'alert-danger',
            'message'       => __('Invalid ID'),
            'admin'         => true,
            'menu_item'     => 'forum',
            'parent_menu'   => 'module_menu',
            'back_url'      => '/admin/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

$req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");

if ($req->rowCount()) {
    $res = $req->fetch();

    if (isset($_POST['submit'])) {
        // Принимаем данные
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
        $sort = isset($_POST['sort']) ? (int) ($_POST['sort']) : 100;
        $section_type = isset($_POST['section_type']) ? (int) ($_POST['section_type']) : 0;
        $category = isset($_POST['category']) ? (int) ($_POST['category']) : 0;
        $allow = isset($_POST['allow']) ? (int) ($_POST['allow']) : 0;

        // проверяем на ошибки
        $error = [];

        if (! $name) {
            $error[] = __('You have not entered Title');
        }

        if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
            $error[] = __('Title') . ': ' . __('Invalid length');
        }

        if ($desc && mb_strlen($desc) < 2) {
            $error[] = __('Description should be at least 2 characters in length');
        }

        if (! $error) {
            // Записываем в базу
            $db->prepare(
                '
                      UPDATE `forum_sections` SET
                      `name` = ?,
                      `description` = ?,
                      `access` = ?,
                      `sort` = ?,
                      `section_type` = ?
                      WHERE `id` = ?
                    '
            )->execute(
                [
                    $name,
                    $desc,
                    $allow,
                    $sort,
                    $section_type,
                    $id,
                ]
            );

            if ($category != $res['parent']) {
                // Вычисляем сортировку
                $req_s = $db->query("SELECT `sort` FROM `forum_sections` WHERE `parent` = '${category}' ORDER BY `sort` DESC LIMIT 1");
                $res_s = $req_s->fetch();
                $sort = $res_s['sort'] + 1;
                // Меняем категорию
                $db->exec("UPDATE `forum_sections` SET `parent` = '${category}', `sort` = '${sort}' WHERE `id` = '${id}'");
                // Меняем категорию для прикрепленных файлов
                $db->exec("UPDATE `cms_forum_files` SET `cat` = '${category}' WHERE `cat` = '" . $res['parent'] . "'");
                if ($res['parent'] == 0) {
                    $db->exec("UPDATE `forum_sections` SET `parent` = '0' WHERE `parent` = '" . $res['id'] . "'");
                }
            }
            header('Location: ?mod=cat' . (! empty($res['parent']) ? '&id=' . $res['parent'] : ''));
        } else {
            // Выводим сообщение об ошибках
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-danger',
                    'message'       => $error,
                    'admin'         => true,
                    'menu_item'     => 'forum',
                    'parent_menu'   => 'module_menu',
                    'back_url'      => '?mod=edit' . ($id ? '&amp;id=' . $id : ''),
                    'back_url_name' => __('Back'),
                ]
            );
        }
    } else {
        // Форма ввода
        $categories = [
            [
                'id'       => 0,
                'name'     => ' - ',
                'selected' => empty($res['parent']),
            ],
        ];
        $tree = [];
        $tools->getSectionsTree($tree);
        foreach ($tree as $item) {
            $categories[] = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'selected' => $item['id'] === $res['parent'],
            ];
        }
        $data['categories'] = $categories;
        $res['name'] = htmlspecialchars($res['name']);
        $res['sort'] = (int) $res['sort'];
        $res['description'] = htmlspecialchars($res['description']);
        $res['access'] = ! empty($res['access']) ? (int) ($res['access']) : 0;
        $res['section_type'] = ! empty($res['section_type']) ? (int) ($res['section_type']) : 0;

        $data['item'] = $res;

        $data['id'] = $id;
        $data['parent_section_name'] = $cat_name ?? '';
        $data['form_action'] = '?mod=edit&amp;id=' . $id;
        $data['back_url'] = '?mod=cat' . (! empty($res['parent']) ? '&amp;id=' . $res['parent'] : '');
        echo $view->render(
            'admin::forum/edit',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
    }
} else {
    header('Location: ?mod=cat');
}
