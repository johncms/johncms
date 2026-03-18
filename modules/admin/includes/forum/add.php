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
    $req = $db->query("SELECT `name` FROM `forum_sections` WHERE `id` = '$id'");

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
    $reservedSlugs = [
        'addfile', 'addvote', 'bulk-delete-posts', 'change-topic', 'close', 'delete-post',
        'delete-post-file', 'delete-topic', 'delvote', 'download-file', 'edit-post', 'editvote',
        'files', 'filter', 'latest-topics', 'move-topic', 'new-message', 'new-topic', 'pin-topic',
        'poll-vote', 'poll-voters', 'post', 'reply-message', 'restore-post', 'restore-topic',
        'search', 'topic-visitors', 'topics-period', 'unread', 'visitors',
    ];

    $generateSlug = static function (PDO $db, string $name, int $parentId, ?int $excludeId = null) use ($reservedSlugs): string {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'section';
        }

        if (in_array($baseSlug, $reservedSlugs, true)) {
            $baseSlug .= '-section';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (true) {
            $query = 'SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = :parent AND `slug` = :slug';
            if ($excludeId !== null) {
                $query .= ' AND `id` != :id';
            }

            $statement = $db->prepare($query);
            $statement->bindValue(':parent', $parentId, PDO::PARAM_INT);
            $statement->bindValue(':slug', $slug, PDO::PARAM_STR);
            if ($excludeId !== null) {
                $statement->bindValue(':id', $excludeId, PDO::PARAM_INT);
            }
            $statement->execute();

            if ((int) $statement->fetchColumn() === 0) {
                break;
            }

            $slug = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    };

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
        $req = $db->query('SELECT `sort`, parent FROM `forum_sections` WHERE ' . ($id ? "`parent` = '$id'" : '1=1') . ' ORDER BY `sort` DESC LIMIT 1');

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
                  `slug` = ?,
                  `description` = ?,
                  `access` = ?,
                  `section_type` = ?,
                  `sort` = ?
                '
        )->execute(
            [
                ($id ? $id : 0),
                $name,
                $generateSlug($db, $name, ($id ? $id : 0)),
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
