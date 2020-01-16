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
 * @var Johncms\System\Users\User $user
 */

if ($user->rights == 3 || $user->rights >= 6) {
    // Массовое удаление выбранных постов форума
    if (isset($_GET['yes'])) {
        $dc = $_SESSION['dc'];
        $prd = $_SESSION['prd'];

        if (! empty($dc)) {
            $db->exec(
                "UPDATE `forum_messages` SET
                `deleted` = '1',
                `deleted_by` = '" . $user->name . "'
                WHERE `id` IN (" . implode(',', $dc) . ')
            '
            );
        }
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Delete posts'),
                'page_title'    => __('Delete posts'),
                'type'          => 'alert-success',
                'message'       => __('Marked posts are deleted'),
                'back_url'      => $prd,
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }
    if (empty($_POST['delch'])) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Delete posts'),
                'page_title'    => __('Delete posts'),
                'type'          => 'alert-danger',
                'message'       => __('You did not choose something to delete'),
                'back_url'      => htmlspecialchars(getenv('HTTP_REFERER')),
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    foreach ($_POST['delch'] as $v) {
        $dc[] = (int) $v;
    }

    $_SESSION['dc'] = $dc;
    $_SESSION['prd'] = htmlspecialchars(getenv('HTTP_REFERER'));
    echo $view->render(
        'forum::mass_delete',
        [
            'title'      => __('Delete posts'),
            'page_title' => __('Delete posts'),
            'back_url'   => htmlspecialchars(getenv('HTTP_REFERER')),
        ]
    );
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
