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

if ($user->rights == 3 || $user->rights >= 6) {
    if (! $id) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Rename topic'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    $ms = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'")->fetch();

    if (empty($ms)) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Rename topic'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if (isset($_POST['submit'])) {
        $nn = isset($_POST['nn']) ? trim($_POST['nn']) : '';

        if (! $nn) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Rename topic'),
                    'type'          => 'alert-danger',
                    'message'       => __('You have not entered topic name'),
                    'back_url'      => '/forum/?act=ren&amp;id=' . $id,
                    'back_url_name' => __('Repeat'),
                ]
            );
            exit;
        }

        // Проверяем, есть ли тема с таким же названием?
        $pt = $db->query("SELECT * FROM `forum_topic` WHERE section_id = '" . $ms['section_id'] . "' AND `name` = " . $db->quote($nn) . ' LIMIT 1');

        if ($pt->rowCount()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Rename topic'),
                    'type'          => 'alert-danger',
                    'message'       => __('Topic with same name already exists in this section'),
                    'back_url'      => '/forum/?act=ren&amp;id=' . $id,
                    'back_url_name' => __('Repeat'),
                ]
            );
            exit;
        }

        $db->exec('UPDATE `forum_topic` SET `name` =' . $db->quote($nn) . " WHERE id='" . $id . "'");
        header("Location: ?type=topic&id=${id}");
        exit;
    }

    echo $view->render(
        'forum::rename_topic',
        [
            'title'      => __('Rename topic'),
            'page_title' => __('Rename topic'),
            'id'         => $id,
            'topic'      => $ms,
            'back_url'   => '?type=topic&id=' . $id,
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
