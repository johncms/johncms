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
 * @var Johncms\System\Users\User $user
 */

if ($user->rights == 3 || $user->rights >= 6) {
    if (empty($_GET['id'])) {
        http_response_code(404);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Forum'),
                'page_title'    => _t('Forum'),
                'type'          => 'alert-danger',
                'message'       => _t('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => _t('Back'),
            ]
        );
        exit;
    }

    if ($db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id` = '" . $id . "'")->fetchColumn()) {
        $db->exec("UPDATE `forum_topic` SET  `pinned` = '" . (isset($_GET['vip']) ? '1' : null) . "' WHERE `id` = '${id}'");
        header('Location: ?type=topic&id=' . $id);
    } else {
        http_response_code(404);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Forum'),
                'page_title'    => _t('Forum'),
                'type'          => 'alert-danger',
                'message'       => _t('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => _t('Back'),
            ]
        );
    }
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
