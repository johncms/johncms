<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumFile;
use Forum\Models\ForumMessage;
use Forum\Models\ForumTopic;
use Forum\Models\ForumUnread;
use Forum\Models\ForumVote;
use Forum\Models\ForumVoteUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var User $user */
$user = di(User::class);

if ($user->rights === 3 || $user->rights >= 6) {
    try {
        $topic = (new ForumTopic())->findOrFail($id);
    } catch (ModelNotFoundException $exception) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Curators'),
                'page_title'    => __('Curators'),
                'type'          => 'alert-danger',
                'message'       => __('Topic has been deleted or does not exists'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }

    if (isset($_POST['submit'])) {
        $del = isset($_POST['del']) ? (int) ($_POST['del']) : null;
        if ($del === 2 && $user->rights === 9) {
            // Удаляем топик
            $files = (new ForumFile())->where('topic', $id)->get();
            if ($files->count() > 0) {
                foreach ($files as $file) {
                    unlink(UPLOAD_PATH . 'forum/attach/' . $file->filename);
                    $file->delete();
                }
            }

            try {
                $topic->delete();
                (new ForumMessage())->where('topic_id', $id)->delete();
                (new ForumVote())->where('topic', $id)->delete();
                (new ForumVoteUser())->where('topic', $id)->delete();
                (new ForumUnread())->where('topic_id', $id)->delete();
            } catch (Exception $e) {
                exit($e->getMessage());
            }
        } elseif ($del = 1) {
            // Скрываем топик
            $topic->update(['deleted' => true, 'deleted_by' => $user->name]);
            (new ForumFile())->where('topic', $id)->update(['del' => 1]);
        }
        header('Location: /forum/?type=topics&id=' . $topic->section_id);
        exit;
    }

    echo $view->render(
        'forum::delete_topic',
        [
            'title'      => __('Delete Topic'),
            'page_title' => __('Delete Topic'),
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
