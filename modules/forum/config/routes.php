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
    $router->map(['GET', 'POST'], '/forum', ForumIndexController::class);
    $router->get('/forum/download-file/{id:number}', DownloadFileController::class);
    $router->get('/forum/files', ForumFilesController::class);
    $router->get('/forum/search', ForumSearchController::class);
    $router->map(['GET', 'POST'], '/forum/search/history/clear', ClearForumSearchHistoryController::class);
    $router->get('/forum/filter/{id:number}', FilterByAuthorController::class);
    $router->post('/forum/filter/{id:number}/clear', ClearFilterByAuthorController::class);
    $router->post('/forum/filter/{id:number}/set', SetFilterByAuthorController::class);
    $router->get('/forum/latest-topics', LatestTopicsController::class);
    $router->get('/forum/post/{id:number}', ShowPostController::class);
    $router->get('/forum/poll-voters/{id:number}', PollVotersController::class);
    $router->get('/forum/visitors', ViewForumVisitorsController::class);
    $router->get('/forum/topic-visitors/{id:number}', ViewTopicVisitorsController::class);
    $router->get('/forum/unread', UnreadTopicsController::class);
    $router->post('/forum/unread/mark-read', MarkAllTopicsReadController::class);
    $router->map(['GET', 'POST'], '/forum/topics-period', TopicsPeriodController::class);

    if ($user->isValid()) {
        $router->post('/forum/upload_file', UploadFileController::class);
        $router->map(['GET', 'POST'], '/forum/addfile/{id:number}', AddFileController::class);
        $router->map(['GET', 'POST'], '/forum/addvote/{id:number}', AddVoteController::class);
        $router->map(['GET', 'POST'], '/forum/change-topic/{id:number}', ChangeTopicController::class);
        $router->map(['GET', 'POST'], '/forum/curators/{id:number}', CuratorsController::class);
        $router->post('/forum/bulk-delete-posts/{id:number}', BulkDeletePostsController::class);
        $router->map(['GET', 'POST'], '/forum/editvote/{id:number}', EditVoteController::class);
        $router->map(['GET', 'POST'], '/forum/delvote/{id:number}', DeleteVoteController::class);
        $router->map(['GET', 'POST'], '/forum/new-topic/{id:number}', NewTopicController::class);
        $router->map(['GET', 'POST'], '/forum/new-message/{id:number}', NewMessageController::class);
        $router->map(['GET', 'POST'], '/forum/reply-message/{id:number}', ReplyMessageController::class);
        $router->map(['GET', 'POST'], '/forum/edit-post/{id:number}', EditPostController::class);
        $router->map(['GET', 'POST'], '/forum/move-topic/{id:number}', MoveTopicController::class);
        $router->map(['GET', 'POST'], '/forum/delete-post/{id:number}', DeletePostController::class);
        $router->map(['GET', 'POST'], '/forum/restore-post/{id:number}', RestorePostController::class);
        $router->map(['GET', 'POST'], '/forum/delete-post-file/{id:number}/{fid:number}', DeletePostFileController::class);
        $router->map(['GET', 'POST'], '/forum/close/{id:number}', CloseTopicController::class);
        $router->map(['GET', 'POST'], '/forum/delete-topic/{id:number}', DeleteTopicController::class);
        $router->map(['GET', 'POST'], '/forum/pin-topic/{id:number}', PinTopicController::class);
        $router->map(['GET', 'POST'], '/forum/restore-topic/{id:number}', RestoreTopicController::class);
        $router->map(['GET', 'POST'], '/forum/poll-vote/{id:number}', SubmitVoteController::class);
    }

    $router->get('/forum/{sectionPath}', ForumPathController::class)->requirements(['sectionPath' => '[a-z0-9\\-/]+']);
};
