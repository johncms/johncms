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
use Johncms\Validator\Validator;

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
    $form_data = [
        'name'         => $request->getPost('name', $section->name, FILTER_SANITIZE_STRING),
        'description'  => $request->getPost('description', $section->description, FILTER_SANITIZE_STRING),
        'sort'         => $request->getPost('sort', $section->sort ?? 100, FILTER_VALIDATE_INT),
        'section_type' => $request->getPost('section_type', $section->section_type ?? 0, FILTER_VALIDATE_INT),
        'parent'       => $request->getPost('parent', $section->parent ?? 0, FILTER_VALIDATE_INT),
        'access'       => $request->getPost('access', $section->access ?? 0, FILTER_VALIDATE_INT),
        'csrf_token'   => $request->getPost('csrf_token'),

        'meta_description' => $request->getPost('meta_description', $section->meta_description ?? '', FILTER_SANITIZE_STRING),
        'meta_keywords'    => $request->getPost('meta_keywords', $section->meta_keywords ?? '', FILTER_SANITIZE_STRING),
    ];

    if ($request->getMethod() === 'POST') {
        // Принимаем данные
        $rules = [
            'name'       => [
                'NotEmpty',
                'StringLength' => ['min' => 2, 'max' => 150],
            ],
            'csrf_token' => [
                'Csrf',
            ],
        ];

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

        $check_child = true;
        if ($isChild($form_data['parent'], $id)) {
            $check_child = false;
        }

        $validator = new Validator($form_data, $rules);
        if ($check_child && $validator->isValid()) {
            if ($form_data['parent'] !== $section->parent) {
                // Вычисляем сортировку
                $new_parent = (new ForumSection())->where('parent', $form_data['parent'])->orderByDesc('sort')->first();
                $sort = $new_parent->sort + 1;
                // Меняем категорию
                $form_data['sort'] = $sort;
                // Меняем категорию для прикрепленных файлов
                (new ForumFile())
                    ->where('cat', $section->parent)
                    ->where('subcat', $section->id)
                    ->update(['cat' => $form_data['parent']]);
            }

            // Записываем в базу
            $section->update($form_data);
            header('Location: ?mod=cat' . (! empty($section->parent) ? '&id=' . $section->parent : ''));
            exit();
        }

        $errors = $validator->getErrors();
        if (! $check_child) {
            $errors['csrf_token'][] = __('Please select a valid parent');
        }
    }

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

    $data['errors'] = $errors ?? [];
    $data['categories'] = $categories;
    $data['item'] = $form_data;
    $data['id'] = $id;
    $data['parent_section_name'] = $cat_name ?? '';
    $data['form_action'] = '?mod=edit&amp;id=' . $id;
    $data['back_url'] = '?mod=cat' . (! empty($section->parent) ? '&amp;id=' . $section->parent : '');

    $view->addData(['title' => $title, 'page_title' => $title]);
    echo $view->render('admin::forum/edit', ['data' => $data]);
} else {
    header('Location: ?mod=cat');
}
