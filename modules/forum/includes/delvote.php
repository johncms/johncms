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
use Forum\Models\ForumVote;
use Forum\Models\ForumVoteUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var User $user */
$user = di(User::class);

if ($user->rights === 3 || $user->rights >= 6) {
    try {
        $topic_vote = (new ForumVote())->where('topic', $id)->where('type', 1)->firstOrFail();
    } catch (ModelNotFoundException $exception) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Delete Poll'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if (isset($_GET['yes'])) {
        (new ForumVote())->where('topic', $id)->delete();
        (new ForumVoteUser())->where('topic', $id)->delete();
        (new ForumTopic())->where('id', $id)->update(['has_poll' => null]);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Delete Poll'),
                'type'          => 'alert-success',
                'message'       => __('Poll deleted'),
                'back_url'      => '/forum/?type=topic&id=' . $id,
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    echo $view->render(
        'forum::delete_poll',
        [
            'title'      => __('Delete Poll'),
            'page_title' => __('Delete Poll'),
            'id'         => $id,
            'back_url'   => '/forum/?type=topic&id=' . $id,
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
