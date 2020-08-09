<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumFile;
use Forum\Models\ForumSection;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 * @var Johncms\System\View\Render $view
 */

$title = __('Edit Section');
$nav_chain->add($title);

$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);

// Редактирование выбранной категории, или раздела
if (empty($id)) {
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

module_lib_loader('forum');

$section = (new ForumSection())->find($id);
if ($section) {
    if (isset($_POST['submit'])) {
        // Принимаем данные
        $name = $request->getPost('name', '', FILTER_SANITIZE_STRING);
        $desc = $request->getPost('desc', '', FILTER_SANITIZE_STRING);
        $sort = $request->getPost('sort', 100, FILTER_VALIDATE_INT);
        $section_type = $request->getPost('section_type', 0, FILTER_VALIDATE_INT);
        $category = $request->getPost('category', 0, FILTER_VALIDATE_INT);
        $allow = $request->getPost('allow', 0, FILTER_VALIDATE_INT);

        // проверяем на ошибки
        $error = [];

        if (! $name) {
            $error[] = __('You have not entered Title');
        }

        if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 150)) {
            $error[] = __('Title') . ': ' . __('Invalid length');
        }

        if ($desc && mb_strlen($desc) < 2) {
            $error[] = __('Description should be at least 2 characters in length');
        }

        $isChild = static function ($parent, $id) use (&$isChild) {
            $parent_section = (new ForumSection())->find($parent);
            if ($parent_section) {
                if ($parent_section->id === $id) {
                    return true;
                }
                return $isChild($parent_section->parent, $id);
            }
            return false;
        };

        if ($isChild($category, $id)) {
            $error[] = __('Please select a valid parent');
        }

        if (! $error) {
            $fields = [
                'name'         => $name,
                'description'  => $desc,
                'access'       => $allow,
                'sort'         => $sort,
                'section_type' => $section_type,
            ];

            if ($category !== $section->parent) {
                // Вычисляем сортировку
                $new_parent = (new ForumSection())->where('parent', $category)->orderByDesc('sort')->first();
                $sort = $new_parent->sort + 1;
                // Меняем категорию
                $fields['parent'] = $category;
                $fields['sort'] = $sort;
                // Меняем категорию для прикрепленных файлов
                (new ForumFile())
                    ->where('cat', $section->parent)
                    ->where('subcat', $section->id)
                    ->update(['cat' => $category]);
            }

            // Записываем в базу
            $section->update($fields);
            header('Location: ?mod=cat' . (! empty($section->parent) ? '&id=' . $section->parent : ''));
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
                'selected' => empty($section->parent),
            ],
        ];
        $tree = [];
        $tools->getSectionsTree($tree);
        foreach ($tree as $item) {
            $categories[] = [
                'id'       => $item['id'],
                'name'     => $item['name'],
                'selected' => $item['id'] === $section->parent,
            ];
        }
        $data['categories'] = $categories;

        $item = [];
        $item['name'] = htmlspecialchars($section->name);
        $item['sort'] = $section->sort;
        $item['description'] = htmlspecialchars($section->description);
        $item['access'] = ! empty($section->access) ? $section->access : 0;
        $item['section_type'] = ! empty($section->section_type) ? $section->section_type : 0;
        $data['item'] = $item;

        $data['id'] = $id;
        $data['parent_section_name'] = $cat_name ?? '';
        $data['form_action'] = '?mod=edit&amp;id=' . $id;
        $data['back_url'] = '?mod=cat' . (! empty($section->parent) ? '&amp;id=' . $section->parent : '');
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
