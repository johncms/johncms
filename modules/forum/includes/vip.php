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

if ($user->rights === 3 || $user->rights >= 6) {
    if (empty($_GET['id'])) {
        http_response_code(404);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Forum'),
                'page_title'    => __('Forum'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if ($db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id` = '" . $id . "'")->fetchColumn()) {
        $db->exec("UPDATE `forum_topic` SET  `pinned` = " . (isset($_GET['vip']) ? 1 : 'NULL') . " WHERE `id` = '${id}'");
        header('Location: ?type=topic&id=' . $id);
    } else {
        http_response_code(404);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Forum'),
                'page_title'    => __('Forum'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
    }
} else {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => __('Access forbidden'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
}
