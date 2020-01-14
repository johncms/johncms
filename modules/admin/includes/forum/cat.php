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
$title = __('Forum structure');
$nav_chain->add($title, '?mod=cat');

// Управление категориями и разделами
if ($id) {
    // Управление разделами
    $req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");
    $res = $req->fetch();
    $nav_chain->add($res['name'], '?mod=cat' . (! empty($res['parent']) ? '&amp;id=' . $res['parent'] : ''));
    $title = __('List of sections');
    $req = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${id}' ORDER BY `sort` ASC");
    if ($req->rowCount()) {
        $items = [];
        while ($res = $req->fetch()) {
            $res['list_url'] = '?mod=cat&amp;id=' . $res['id'];
            $res['public_url'] = '/forum/?' . ($res['section_type'] === 1 ? 'type=topics&amp;' : '') . 'id=' . $res['id'];
            $res['description'] = htmlspecialchars($res['description']);
            $res['counter'] = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '" . $res['id'] . "'")->fetchColumn();
            $res['edit_url'] = '?mod=edit&amp;id=' . $res['id'];
            $res['delete_url'] = '?mod=del&amp;id=' . $res['id'];
            $items[] = $res;
        }
    }
} else {
    // Управление категориями
    $title = __('List of categories');
    $req = $db->query('SELECT * FROM `forum_sections` WHERE `parent` = 0 OR `parent` IS NULL ORDER BY `sort` ASC');
    $items = [];
    while ($res = $req->fetch()) {
        $res['list_url'] = '?mod=cat&amp;id=' . $res['id'];
        $res['public_url'] = '/forum/?id=' . $res['id'];
        $res['description'] = htmlspecialchars($res['description']);
        $res['counter'] = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '" . $res['id'] . "'")->fetchColumn();
        $res['edit_url'] = '?mod=edit&amp;id=' . $res['id'];
        $res['delete_url'] = '?mod=del&amp;id=' . $res['id'];
        $items[] = $res;
    }
}

$data['items'] = $items ?? [];
$data['back_url'] = $id ? '?mod=cat' : '/admin/forum/';
$data['add_form_url'] = '?mod=add' . ($id ? '&amp;id=' . $id : '');
echo $view->render(
    'admin::forum/structure',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
