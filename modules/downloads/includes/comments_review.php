<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

// Обзор комментариев
if (! $config['mod_down_comm'] && $user->rights < 7) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Comments are disabled'),
            'type'          => 'alert-danger',
            'message'       => __('Comments are disabled'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$title = __('Review comments');

$data = [];

$total = $db->query('SELECT COUNT(*) FROM `download__comments`')->fetchColumn();

if ($total) {
    $req = $db->query(
        "SELECT `download__comments`.*, `download__comments`.`id` AS `cid`, `users`.`rights`, `users`.`name`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`, `download__files`.`rus_name`
	    FROM `download__comments`
	    LEFT JOIN `users` ON `download__comments`.`user_id` = `users`.`id`
	    LEFT JOIN `download__files` ON `download__comments`.`sub_id` = `download__files`.`id`
	    ORDER BY `download__comments`.`time` DESC
	    LIMIT ${start}, " . $user->config->kmess
    );

    // Выводим список
    $items = [];
    while ($res = $req->fetch()) {
        $attributes = unserialize($res['attributes'], ['allowed_classes' => false]);
        $res['name'] = $attributes['author_name'];

        $res['ip'] = $attributes['author_ip'];
        $res['ip_via_proxy'] = $attributes['author_ip_via_proxy'] ?? 0;
        $res['user_agent'] = $attributes['author_browser'];
        $res['created'] = $tools->displayDate($res['time']);

        $res['rus_name'] = htmlspecialchars($res['rus_name']);

        $res['reply_url'] = '';
        $res['edit_url'] = '';
        $res['delete_url'] = '';
        $res['has_edit'] = '';
        $res['file_url'] = '?act=view&amp;id=' . $res['sub_id'];
        $res['comments_url'] = '?act=comments&amp;id=' . $res['sub_id'];

        $text = $tools->checkout($res['text'], 1, 1);
        $text = $tools->smilies($text, $res['rights'] >= 1 ? 1 : 0);

        $res['post_text'] = $text;

        $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res['ip']);
        $res['ip'] = long2ip((int) $res['ip']);
        $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res['ip_via_proxy']);
        $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip((int) $res['ip_via_proxy']) : 0;

        $res['edit_count'] = $attributes['edit_count'] ?? 0;
        $res['editor_name'] = $attributes['edit_name'] ?? '';
        $res['edit_time'] = ! empty($attributes['edit_time']) ? $tools->displayDate($attributes['edit_time']) : '';

        $res['reply_text'] = '';
        if (! empty($res['reply'])) {
            $reply = $tools->checkout($res['reply'], 1, 1);
            $reply = $tools->smilies($reply, $attributes['reply_rights'] >= 1 ? 1 : 0);
            $res['reply_text'] = $reply;
            $res['reply_time'] = $tools->displayDate($attributes['reply_time']);
            $res['reply_author_url'] = '/profile/?user=' . $attributes['reply_id'];
            $res['reply_author_name'] = $attributes['reply_name'];
        }
        $items[] = $res;
    }
}

$data['items'] = $items ?? [];

if ($total > $user->config->kmess) {
    $data['pagination'] = $tools->displayPagination('?act=review_comments&amp;', $start, $total, $user->config->kmess);
}

echo $view->render(
    'downloads::comments_review',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
        'urls'       => $urls,
    ]
);
