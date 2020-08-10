<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumTopic;
use Illuminate\Database\Eloquent\ModelNotFoundException;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

if ($user->rights !== 3 && $user->rights < 6) {
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

$view->addData(['title' => __('Change the topic'), 'page_title' => __('Change the topic')]);

try {
    $current_topic = (new ForumTopic())->findOrFail($id);
} catch (ModelNotFoundException $exception) {
    echo $view->render(
        'system::pages/result',
        [
            'type'          => 'alert-danger',
            'message'       => $exception->getMessage(),
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
                'type'          => 'alert-danger',
                'message'       => __('You have not entered topic name'),
                'back_url'      => '/forum/?act=ren&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }

    // Проверяем, есть ли тема с таким же названием?
    $check_topic = (new ForumTopic())->where('section_id', $current_topic->section_id)->where('name', $nn)->first();
    if ($check_topic) {
        echo $view->render(
            'system::pages/result',
            [
                'type'          => 'alert-danger',
                'message'       => __('Topic with same name already exists in this section'),
                'back_url'      => '/forum/?act=ren&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }

    $current_topic->update(['name' => $nn]);

    header("Location: ?type=topic&id=${id}");
    exit;
}

echo $view->render(
    'forum::change_topic',
    [
        'id'       => $id,
        'topic'    => $current_topic,
        'back_url' => '?type=topic&id=' . $id,
    ]
);
