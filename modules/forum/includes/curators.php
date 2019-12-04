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
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface $user
 */

if ($user->rights >= 7) {
    $req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");

    if (! $req->rowCount()) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Curators'),
                'page_title'    => _t('Curators'),
                'type'          => 'alert-danger',
                'message'       => _t('Topic has been deleted or does not exists'),
                'back_url'      => '/forum/',
                'back_url_name' => _t('Back'),
            ]
        );
        exit;
    }

    $topic = $req->fetch();
    $req = $db->query(
        "SELECT `forum_messages`.*, `users`.`id`
        FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
        WHERE `forum_messages`.`topic_id`='${id}' AND `users`.`rights` < 6 AND `users`.`rights` != 3 GROUP BY `forum_messages`.`user_id` ORDER BY `forum_messages`.`user_name`"
    );
    $total = $req->rowCount();
    $curators = [];
    $users = ! empty($topic['curators']) ? unserialize($topic['curators'], ['allowed_classes' => false]) : [];

    if (isset($_POST['submit'])) {
        $users = $_POST['users'] ?? [];
        if (! is_array($users)) {
            $users = [];
        }
    }

    if ($total > 0) {
        while ($res = $req->fetch()) {
            $checked = array_key_exists($res['user_id'], $users);
            if ($checked) {
                $curators[$res['user_id']] = $res['user_name'];
            }
            $res['checked'] = $checked;
            $curators_list[] = $res;
        }

        if (isset($_POST['submit'])) {
            $db->exec('UPDATE `forum_topic` SET `curators`=' . $db->quote(serialize($curators)) . " WHERE `id` = '${id}'");
            $saved = true;
        }
    }

    echo $view->render(
        'forum::curators',
        [
            'title'         => _t('Curators'),
            'page_title'    => _t('Curators'),
            'id'            => $id,
            'start'         => $start,
            'back_url'      => '?type=topic&id=' . $id . '&amp;start=' . $start,
            'total'         => $total,
            'curators_list' => $curators_list ?? [],
            'topic'         => $topic ?? [],
            'saved'         => $saved ?? false,
        ]
    );
} else {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => _t('Access forbidden'),
            'back_url'      => '/forum/',
            'back_url_name' => _t('Back'),
        ]
    );
}
