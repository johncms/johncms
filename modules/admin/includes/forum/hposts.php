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

$title = __('Hidden posts');
$nav_chain->add($title);

// Управление скрытыми постави форума
$sort = '';
$link = '';
$data = [];

$data['reset_filter'] = '?mod=hposts';
if (isset($_GET['tsort'])) {
    $sort = " AND `forum_messages`.`topic_id` = '" . abs((int) ($_GET['tsort'])) . "'";
    $link = '&amp;tsort=' . abs((int) ($_GET['tsort']));
    $data['filtered_by'] = __('by topic');
} elseif (isset($_GET['usort'])) {
    $sort = " AND `forum_messages`.`user_id` = '" . abs((int) ($_GET['usort'])) . "'";
    $link = '&amp;usort=' . abs((int) ($_GET['usort']));
    $data['filtered_by'] = __('by author');
}

if (isset($_POST['delpost'])) {
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

    $req = $db->query("SELECT `id` FROM `forum_messages` WHERE `deleted` = '1' ${sort}");

    while ($res = $req->fetch()) {
        $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");

        if ($req_f->rowCount()) {
            while ($res_f = $req_f->fetch()) {
                // Удаляем файлы
                unlink(UPLOAD_PATH . 'forum/attach/' . $res_f['filename']);
            }
            $db->exec("DELETE FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
        }
    }

    // Удаляем посты
    $db->exec("DELETE FROM `forum_messages` WHERE `deleted` = '1' ${sort}");

    header('Location: ?mod=hposts');
} else {
    $total = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` = '1' ${sort}")->fetchColumn();
    $req = $db->query(
        "SELECT `forum_messages`.*,
            `forum_messages`.`id` AS `fid`,
            `forum_messages`.`user_id` AS `id`,
            `forum_messages`.`user_name` AS `name`,
            `forum_messages`.`user_agent` AS `browser`,
            `users`.`rights`,
            `users`.`lastdate`,
            `users`.`sex`,
            `users`.`status`,
            `users`.`datereg`
            FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
            WHERE `forum_messages`.`deleted` = '1' ${sort} ORDER BY `forum_messages`.`id` DESC LIMIT " . $start . ',' . $user->config->kmess
    );

    if ($req->rowCount()) {
        $items = [];
        while ($res = $req->fetch()) {
            $res['display_date'] = $tools->displayDate($res['date']);
            $count_mess = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '" . $res['fid'] . "'")->fetchColumn();
            $page = ceil($count_mess / $user->config->kmess);

            $text = mb_substr($res['text'], 0, 500);
            $text = $tools->checkout($text, 1, 0);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
            $res['formatted_text'] = $text;

            $theme = $db->query("SELECT `id`, `name` FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
            $res['topic_name'] = $theme['name'];
            $res['topic_url'] = '/forum/?type=topic&id=' . $theme['id'] . '&amp;page=' . $page;
            $res['buttons'] = [
                [
                    'url'  => '?mod=hposts&amp;tsort=' . $theme['id'],
                    'name' => __('by topic'),
                ],
                [
                    'url'  => '?mod=hposts&amp;usort=' . $res['user_id'],
                    'name' => __('by author'),
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
        $data['del_all_url'] = '?mod=hposts' . $link;
    }

    $data['items'] = $items ?? [];
    $data['total'] = $total;
    $data['back_url'] = '/admin/forum/';
    if ($total > $user->config->kmess) {
        $data['pagination'] = $tools->displayPagination('?mod=hposts&amp;', $start, $total, $user->config->kmess);
    }

    echo $view->render(
        'admin::forum/hidden_posts',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
}
