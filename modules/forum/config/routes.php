<?php

declare(strict_types=1);

use Johncms\Modules\Forum\Application\Controllers\AddFileController;
use Johncms\Modules\Forum\Application\Controllers\AddVoteController;
use Johncms\Modules\Forum\Application\Controllers\BulkDeletePostsController;
use Johncms\Modules\Forum\Application\Controllers\ChangeTopicController;
use Johncms\Modules\Forum\Application\Controllers\ClearFilterByAuthorController;
use Johncms\Modules\Forum\Application\Controllers\ClearForumSearchHistoryController;
use Johncms\Modules\Forum\Application\Controllers\CloseTopicController;
use Johncms\Modules\Forum\Application\Controllers\CuratorsController;
use Johncms\Modules\Forum\Application\Controllers\DeletePostController;
use Johncms\Modules\Forum\Application\Controllers\DeletePostFileController;
use Johncms\Modules\Forum\Application\Controllers\DeleteTopicController;
use Johncms\Modules\Forum\Application\Controllers\DeleteVoteController;
use Johncms\Modules\Forum\Application\Controllers\DownloadFileController;
use Johncms\Modules\Forum\Application\Controllers\EditPostController;
use Johncms\Modules\Forum\Application\Controllers\EditVoteController;
use Johncms\Modules\Forum\Application\Controllers\FilterByAuthorController;
use Johncms\Modules\Forum\Application\Controllers\ForumFilesController;
use Johncms\Modules\Forum\Application\Controllers\ForumIndexController;
use Johncms\Modules\Forum\Application\Controllers\ForumPathController;
use Johncms\Modules\Forum\Application\Controllers\ForumSearchController;
use Johncms\Modules\Forum\Application\Controllers\LatestTopicsController;
use Johncms\Modules\Forum\Application\Controllers\MarkAllTopicsReadController;
use Johncms\Modules\Forum\Application\Controllers\MoveTopicController;
use Johncms\Modules\Forum\Application\Controllers\NewMessageController;
use Johncms\Modules\Forum\Application\Controllers\NewTopicController;
use Johncms\Modules\Forum\Application\Controllers\PinTopicController;
use Johncms\Modules\Forum\Application\Controllers\PollVotersController;
use Johncms\Modules\Forum\Application\Controllers\ReplyMessageController;
use Johncms\Modules\Forum\Application\Controllers\RestorePostController;
use Johncms\Modules\Forum\Application\Controllers\RestoreTopicController;
use Johncms\Modules\Forum\Application\Controllers\SetFilterByAuthorController;
use Johncms\Modules\Forum\Application\Controllers\ShowPostController;
use Johncms\Modules\Forum\Application\Controllers\SubmitVoteController;
use Johncms\Modules\Forum\Application\Controllers\TopicsPeriodController;
use Johncms\Modules\Forum\Application\Controllers\UnreadTopicsController;
use Johncms\Modules\Forum\Application\Controllers\UploadFileController;
use Johncms\Modules\Forum\Application\Controllers\ViewForumVisitorsController;
use Johncms\Modules\Forum\Application\Controllers\ViewTopicVisitorsController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->map(['GET', 'POST'], '/forum', ForumIndexController::class)->name('forum.index');
    $router->get('/forum/download-file/{id:number}', DownloadFileController::class)->name('forum.download_file');
    $router->get('/forum/files', ForumFilesController::class)->name('forum.files');
    $router->get('/forum/search', ForumSearchController::class)->name('forum.search');
    $router->map(['GET', 'POST'], '/forum/search/history/clear', ClearForumSearchHistoryController::class)->name('forum.search_history_clear');
    $router->get('/forum/filter/{id:number}', FilterByAuthorController::class)->name('forum.filter');
    $router->post('/forum/filter/{id:number}/clear', ClearFilterByAuthorController::class)->name('forum.filter_clear');
    $router->post('/forum/filter/{id:number}/set', SetFilterByAuthorController::class)->name('forum.filter_set');
    $router->get('/forum/latest-topics', LatestTopicsController::class)->name('forum.latest_topics');
    $router->get('/forum/post/{id:number}', ShowPostController::class)->name('forum.post');
    $router->get('/forum/poll-voters/{id:number}', PollVotersController::class)->name('forum.poll_voters');
    $router->get('/forum/visitors', ViewForumVisitorsController::class)->name('forum.visitors');
    $router->get('/forum/topic-visitors/{id:number}', ViewTopicVisitorsController::class)->name('forum.topic_visitors');
    $router->get('/forum/unread', UnreadTopicsController::class)->name('forum.unread');
    $router->post('/forum/unread/mark-read', MarkAllTopicsReadController::class)->name('forum.unread_mark_read');
    $router->map(['GET', 'POST'], '/forum/topics-period', TopicsPeriodController::class)->name('forum.topics_period');

    if ($user->isValid()) {
        $router->post('/forum/upload_file', UploadFileController::class)->name('forum.upload_file');
        $router->map(['GET', 'POST'], '/forum/addfile/{id:number}', AddFileController::class)->name('forum.add_file');
        $router->map(['GET', 'POST'], '/forum/addvote/{id:number}', AddVoteController::class)->name('forum.add_vote');
        $router->map(['GET', 'POST'], '/forum/change-topic/{id:number}', ChangeTopicController::class)->name('forum.change_topic');
        $router->map(['GET', 'POST'], '/forum/curators/{id:number}', CuratorsController::class)->name('forum.curators');
        $router->post('/forum/bulk-delete-posts/{id:number}', BulkDeletePostsController::class)->name('forum.bulk_delete_posts');
        $router->map(['GET', 'POST'], '/forum/editvote/{id:number}', EditVoteController::class)->name('forum.edit_vote');
        $router->map(['GET', 'POST'], '/forum/delvote/{id:number}', DeleteVoteController::class)->name('forum.delete_vote');
        $router->map(['GET', 'POST'], '/forum/new-topic/{id:number}', NewTopicController::class)->name('forum.new_topic');
        $router->map(['GET', 'POST'], '/forum/new-message/{id:number}', NewMessageController::class)->name('forum.new_message');
        $router->map(['GET', 'POST'], '/forum/reply-message/{id:number}', ReplyMessageController::class)->name('forum.reply_message');
        $router->map(['GET', 'POST'], '/forum/edit-post/{id:number}', EditPostController::class)->name('forum.edit_post');
        $router->map(['GET', 'POST'], '/forum/move-topic/{id:number}', MoveTopicController::class)->name('forum.move_topic');
        $router->map(['GET', 'POST'], '/forum/delete-post/{id:number}', DeletePostController::class)->name('forum.delete_post');
        $router->map(['GET', 'POST'], '/forum/restore-post/{id:number}', RestorePostController::class)->name('forum.restore_post');
        $router->map(['GET', 'POST'], '/forum/delete-post-file/{id:number}/{fid:number}', DeletePostFileController::class)->name('forum.delete_post_file');
        $router->map(['GET', 'POST'], '/forum/close/{id:number}', CloseTopicController::class)->name('forum.close_topic');
        $router->map(['GET', 'POST'], '/forum/delete-topic/{id:number}', DeleteTopicController::class)->name('forum.delete_topic');
        $router->map(['GET', 'POST'], '/forum/pin-topic/{id:number}', PinTopicController::class)->name('forum.pin_topic');
        $router->map(['GET', 'POST'], '/forum/restore-topic/{id:number}', RestoreTopicController::class)->name('forum.restore_topic');
        $router->map(['GET', 'POST'], '/forum/poll-vote/{id:number}', SubmitVoteController::class)->name('forum.poll_vote');
    }

    $router->get('/forum/{sectionPath}', ForumPathController::class)->name('forum.section')->requirements(['sectionPath' => '[a-z0-9\\-/]+']);
};
