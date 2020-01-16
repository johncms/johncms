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

$title = ($id ? __('Add Section') : __('Add Category'));
$nav_chain->add($title);

// Добавление категории
if ($id) {
    // Проверяем наличие категории
    $req = $db->query("SELECT `name` FROM `forum_sections` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $cat_name = $res['name'];
    } else {
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
}

if (isset($_POST['submit'])) {
    // Принимаем данные
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
    $allow = isset($_POST['allow']) ? (int) ($_POST['allow']) : 0;
    $section_type = isset($_POST['section_type']) ? (int) ($_POST['section_type']) : 0;

    // Проверяем на ошибки
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
        // Добавляем в базу категорию
        $req = $db->query('SELECT `sort`, parent FROM `forum_sections` WHERE ' . ($id ? "`parent` = '${id}'" : '1=1') . ' ORDER BY `sort` DESC LIMIT 1');

        if ($req->rowCount()) {
            $res = $req->fetch();
            $sort = $res['sort'] + 1;
        } else {
            $sort = 1;
        }

        $db->prepare(
            '
                  INSERT INTO `forum_sections` SET
                  `parent` = ?,
                  `name` = ?,
                  `description` = ?,
                  `access` = ?,
                  `section_type` = ?,
                  `sort` = ?
                '
        )->execute(
            [
                ($id ? $id : 0),
                $name,
                $desc,
                $allow,
                $section_type,
                $sort,
            ]
        );

        header('Location: ?mod=cat' . ($id ? '&id=' . $id : ''));
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
                'back_url'      => '?mod=add' . ($id ? '&amp;id=' . $id : ''),
                'back_url_name' => __('Back'),
            ]
        );
    }
} else {
    // Форма ввода
    $data['id'] = $id;
    $data['parent_section_name'] = $cat_name ?? '';
    $data['form_action'] = '?mod=add' . ($id ? '&amp;id=' . $id : '');
    $data['back_url'] = '?mod=cat' . ($id ? '&amp;id=' . $id : '');
    echo $view->render(
        'admin::forum/add',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
