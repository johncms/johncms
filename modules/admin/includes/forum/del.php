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

// Удаление категории, или раздела
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
    $title = ($res['section_type'] != 1 ? __('Delete section') : __('Delete category')) . ': ' . $res['name'];
    // Проверяем, есть ли подчиненная информация
    if (! empty($res['section_type'])) {
        $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `section_id` = '${id}'")->fetchColumn();
    } else {
        $total = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '${id}'")->fetchColumn();
    }

    if ($total) {
        if (empty($res['section_type'])) {
            // Удаление категории с подчиненными данными
            if (isset($_POST['submit'])) {
                $category = isset($_POST['category']) ? (int) ($_POST['category']) : 0;

                if (! $category || $category == $id) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => $title,
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'admin'         => true,
                            'menu_item'     => 'forum',
                            'parent_menu'   => 'module_menu',
                            'back_url'      => '/admin/forum/',
                            'back_url_name' => __('Back'),
                        ]
                    );
                    exit;
                }

                $check = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `id` = '${category}'")->fetchColumn();

                if (! $check) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => $title,
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'admin'         => true,
                            'menu_item'     => 'forum',
                            'parent_menu'   => 'module_menu',
                            'back_url'      => '/admin/forum/',
                            'back_url_name' => __('Back'),
                        ]
                    );
                    exit;
                }

                // Вычисляем правила сортировки и перемещаем разделы
                $sort = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${category}' ORDER BY `sort` DESC")->fetch();
                $sortnum = ! empty($sort['sort']) && $sort['sort'] > 0 ? $sort['sort'] + 1 : 1;
                $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${id}'");

                while ($res_c = $req_c->fetch()) {
                    $db->exec("UPDATE `forum_sections` SET `parent` = '" . $category . "', `sort` = '${sortnum}' WHERE `id` = " . $res_c['id']);
                    ++$sortnum;
                }

                // Перемещаем файлы в выбранную категорию
                $db->exec("UPDATE `cms_forum_files` SET `cat` = '" . $category . "' WHERE `cat` = " . $res['id']);
                $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('Category deleted'),
                        'type'          => 'alert-success',
                        'message'       => __('All content has been moved to') . ' <a href="/forum/?id=' . $category . '">' . __('selected category') . '</a>',
                        'admin'         => true,
                        'menu_item'     => 'forum',
                        'parent_menu'   => 'module_menu',
                        'back_url'      => '/admin/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
            } else {
                $categories = [];
                $req_c = $db->query("SELECT * FROM `forum_sections` WHERE (`section_type` != 1 OR section_type IS NULL) AND `id` != '${id}' ORDER BY `sort` ASC");
                while ($res_c = $req_c->fetch()) {
                    $categories[] = [
                        'id'       => $res_c['id'],
                        'name'     => $res_c['name'],
                        'selected' => $res_c['id'] === $res['parent'],
                    ];
                }
                $data['id'] = $id;
                $data['categories'] = $categories;
                $data['form_action'] = '?mod=del&amp;id=' . $id;
                $data['back_url'] = '?mod=cat';
                echo $view->render(
                    'admin::forum/del_confirm_move',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                        'data'       => $data,
                    ]
                );
            }
        } elseif (isset($_POST['submit'])) {
            // Удаление раздела с подчиненными данными
            // Предварительные проверки
            $subcat = isset($_POST['subcat']) ? (int) ($_POST['subcat']) : 0;

            if (! $subcat || $subcat == $id) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'admin'         => true,
                        'menu_item'     => 'forum',
                        'parent_menu'   => 'module_menu',
                        'back_url'      => '/admin/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            $check = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `id` = '${subcat}' AND `section_type` = 1")->fetchColumn();

            if (! $check) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'admin'         => true,
                        'menu_item'     => 'forum',
                        'parent_menu'   => 'module_menu',
                        'back_url'      => '/admin/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            $db->exec("UPDATE `forum_topic` SET `section_id` = '${subcat}' WHERE `section_id` = '${id}'");
            $db->exec("UPDATE `cms_forum_files` SET `subcat` = '${subcat}' WHERE `subcat` = '${id}'");
            $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Section deleted'),
                    'type'          => 'alert-success',
                    'message'       => __('All content has been moved to') . ' <a href="/forum/?id=' . $subcat . '">' . __('selected section') . '</a>',
                    'admin'         => true,
                    'menu_item'     => 'forum',
                    'parent_menu'   => 'module_menu',
                    'back_url'      => '/admin/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } elseif (isset($_POST['delete'])) {
            if ($user->rights !== 9) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('Access denied'),
                        'admin'         => true,
                        'menu_item'     => 'forum',
                        'parent_menu'   => 'module_menu',
                        'back_url'      => '/admin/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            // Удаляем файлы
            $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `subcat` = '${id}'");

            while ($res_f = $req_f->fetch()) {
                unlink(UPLOAD_PATH . 'forum/attach/' . $res_f['filename']);
            }

            $db->exec("DELETE FROM `cms_forum_files` WHERE `subcat` = '${id}'");

            // Удаляем посты, голосования и метки прочтений
            $req_t = $db->query("SELECT `id` FROM `forum_topic` WHERE `section_id` = '${id}'");

            while ($res_t = $req_t->fetch()) {
                $db->exec("DELETE FROM `forum_messages` WHERE `topic_id` = '" . $res_t['id'] . "'");
                $db->exec("DELETE FROM `cms_forum_vote` WHERE `topic` = '" . $res_t['id'] . "'");
                $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '" . $res_t['id'] . "'");
                $db->exec("DELETE FROM `cms_forum_rdm` WHERE `topic_id` = '" . $res_t['id'] . "'");
            }

            // Удаляем темы
            $db->exec("DELETE FROM `forum_topic` WHERE `section_id` = '${id}'");
            // Удаляем раздел
            $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
            // Оптимизируем таблицы
            $db->query('OPTIMIZE TABLE `cms_forum_files` , `cms_forum_rdm` , `cms_forum_vote` , `cms_forum_vote_users`');
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('Section with all contents are removed'),
                    'admin'         => true,
                    'menu_item'     => 'forum',
                    'parent_menu'   => 'module_menu',
                    'back_url'      => '?mod=cat&amp;id=' . $res['parent'],
                    'back_url_name' => __('Go to category'),
                ]
            );
        } else {
            $sections = [];
            $cat = isset($_GET['cat']) ? abs((int) ($_GET['cat'])) : 0;
            $ref = $cat ? $cat : $res['parent'];
            $req_r = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${ref}' AND `id` != '${id}' ORDER BY `sort` ASC");
            while ($res_r = $req_r->fetch()) {
                $sections[] = $res_r;
            }
            $categories = [];
            $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `id` != '${ref}' AND parent = 0 ORDER BY `sort` ASC");
            while ($res_c = $req_c->fetch()) {
                $categories[] = $res_c;
            }

            $data['id'] = $id;
            $data['sections'] = $sections;
            $data['categories'] = $categories;
            $data['form_action'] = '?mod=del&amp;id=' . $id;
            $data['back_url'] = '?mod=cat';
            echo $view->render(
                'admin::forum/del_confirm_move_topics',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
    } elseif (isset($_POST['submit'])) {
        // Удаление пустого раздела, или категории
        $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => ($res['section_type'] === 1 ? __('Section deleted') : __('Category deleted')),
                'admin'         => true,
                'menu_item'     => 'forum',
                'parent_menu'   => 'module_menu',
                'back_url'      => '?mod=cat&amp;id=' . $res['parent'],
                'back_url_name' => __('Go to category'),
            ]
        );
    } else {
        $data['message'] = __('Do you really want to delete?');
        $data['form_action'] = '?mod=del&amp;id=' . $id;
        $data['back_url'] = '?mod=cat';
        echo $view->render(
            'admin::forum/del_confirm',
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
