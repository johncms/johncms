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
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '${id}'")->fetchColumn();

if ($topic_vote == 0 || $user->rights < 7) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Download topic'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Forum'),
        ]
    );
    exit;
}
$topic_vote = $db->query("SELECT `name`, `time`, `count` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '${id}' LIMIT 1")->fetch();
$total = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `topic`='${id}'")->fetchColumn();
$req = $db->query(
    "SELECT `cms_forum_vote_users`.*, `users`.`rights`, `users`.`lastdate`, `users`.`name`, `users`.`sex`, `users`.`status`, `users`.`datereg`,
       `users`.`id`, users.ip, users.ip_via_proxy, users.browser
    FROM `cms_forum_vote_users` LEFT JOIN `users` ON `cms_forum_vote_users`.`user` = `users`.`id`
    WHERE `cms_forum_vote_users`.`topic`='${id}' LIMIT ${start}, " . $user->config->kmess
);

$items = [];
while ($res = $req->fetch()) {
    $res['user_profile_link'] = '';
    if (! empty($res['id']) && $user->isValid() && $user->id != $res['id']) {
        $res['user_profile_link'] = '/profile/?user=' . $res['id'];
    }

    $res['user_rights_name'] = '';
    if (! empty($res['rights'])) {
        $res['user_rights_name'] = $user_rights_names[$res['rights']] ?? '';
    }

    $res['user_is_online'] = time() <= $res['lastdate'] + 300;

    $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res['ip']);
    $res['ip'] = long2ip((int) $res['ip']);
    $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip((int) $res['ip_via_proxy']);
    $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip((int) $res['ip_via_proxy']) : 0;

    $res['place'] = '';
    $items[] = $res;
}

echo $view->render(
    'forum::voted_users',
    [
        'title'         => __('Who voted in the poll'),
        'page_title'    => __('Who voted in the poll'),
        'empty_message' => __('No one has voted in this poll yet'),
        'poll_name'     => htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8'),
        'items'         => $items ?? [],
        'pagination'    => $tools->displayPagination('?act=users&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess),
        'total'         => $total,
        'id'            => $id,
    ]
);
