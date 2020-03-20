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

$title = __('Hidden topics');
$nav_chain->add($title);

// Управление скрытыми темами форума
$sort = '';
$link = '';

$data['reset_filter'] = '?mod=htopics';
if (isset($_GET['usort'])) {
    $sort = " AND `forum_topic`.`user_id` = '" . abs((int) ($_GET['usort'])) . "'";
    $link = '&amp;usort=' . abs((int) ($_GET['usort']));
    $data['filtered_by'] = __('by author');
}

if (isset($_GET['rsort'])) {
    $sort = " AND `forum_topic`.`section_id` = '" . abs((int) ($_GET['rsort'])) . "'";
    $link = '&amp;rsort=' . abs((int) ($_GET['rsort']));
    $data['filtered_by'] = __('by section');
}

if (isset($_POST['deltopic'])) {
    if ($user->rights != 9) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => __('Access forbidden'),
                'admin'         => true,
                'menu_item'     => 'forum',
                'parent_menu'   => 'module_menu',
                'back_url'      => '/admin/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    $req = $db->query("SELECT `id` FROM `forum_topic` WHERE `deleted` = '1' ${sort}");
    while ($res = $req->fetch()) {
        $req_f = $db->query('SELECT * FROM `cms_forum_files` WHERE `topic` = ' . $res['id']);
        if ($req_f->rowCount()) {
            // Удаляем файлы
            while ($res_f = $req_f->fetch()) {
                unlink(UPLOAD_PATH . 'forum/attach/' . $res_f['filename']);
            }
            $db->exec('DELETE FROM `cms_forum_files` WHERE `topic` = ' . $res['id']);
        }
        // Удаляем посты
        $db->exec('DELETE FROM `forum_messages` WHERE `topic_id` = ' . $res['id']);
    }
    // Удаляем темы
    $db->exec("DELETE FROM `forum_topic` WHERE `deleted` = '1' ${sort}");

    header('Location: ?mod=htopics');
} else {
    $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` = '1' ${sort}")->fetchColumn();
    $req = $db->query(
        "SELECT `forum_topic`.*,
            `forum_topic`.`id` AS `fid`,
            `forum_topic`.`name` AS `topic_name`,
            `forum_topic`.`user_id` AS `id`,
            `forum_topic`.`user_name` AS `name`,
            `users`.`rights`,
            `users`.`lastdate`,
            `users`.`sex`,
            `users`.`status`,
            `users`.`datereg`,
            `users`.`ip`,
            `users`.`browser`,
            `users`.`ip_via_proxy`
            FROM `forum_topic` LEFT JOIN `users` ON `forum_topic`.`user_id` = `users`.`id`
            WHERE `forum_topic`.`deleted` = '1' ${sort} ORDER BY `forum_topic`.`id` DESC LIMIT " . $start . ',' . $user->config->kmess
    );

    if ($req->rowCount()) {
        while ($res = $req->fetch()) {
            $subcat = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res['section_id'] . "'")->fetch();
            $cat = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $subcat['parent'] . "'")->fetch();

            $res['display_date'] = $tools->displayDate($res['mod_last_post_date']);
            $res['topic_url'] = '/forum/?type=topic&id=' . $res['fid'];
            $res['buttons'] = [
                [
                    'url'  => '?mod=htopics&amp;rsort=' . $res['section_id'],
                    'name' => __('by author'),
                ],
                [
                    'url'  => '?mod=htopics&amp;usort=' . $res['user_id'],
                    'name' => __('by section'),
                ],
            ];
            $res['path'] = [
                [
                    'url'  => '/forum/?id=' . $cat['id'],
                    'name' => $cat['name'],
                ],
                [
                    'url'  => '/forum/?type=topics&id=' . $subcat['id'],
                    'name' => $subcat['name'],
                ],
            ];

            $res['user_profile_link'] = '';
            if (! empty($res['id']) && $user->id !== $res['id']) {
                $res['user_profile_link'] = '/profile/?user=' . $res['id'];
            }
            $res['user_is_online'] = time() <= $res['lastdate'] + 300;
            $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res['ip']);
            $res['ip'] = long2ip((int) $res['ip']);
            $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip((int) $res['ip_via_proxy']) : 0;
            $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $res['ip_via_proxy'];

            $items[] = $res;
        }
    }

    if ($user->rights === 9 && $total > 0) {
        $data['del_all_url'] = '?mod=htopics' . $link;
    }

    $data['items'] = $items ?? [];
    $data['total'] = $total;
    $data['back_url'] = '/admin/forum/';
    if ($total > $user->config->kmess) {
        $data['pagination'] = $tools->displayPagination('?mod=htopics&amp;', $start, $total, $user->config->kmess);
    }

    echo $view->render(
        'admin::forum/hidden_topics',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
