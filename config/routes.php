<?php

declare(strict_types=1);

use Johncms\Modules\Admin\Application\Controllers\System\SystemCheckController;
use Johncms\Modules\Admin\Application\Controllers\Users\UsersController;
use Johncms\Modules\Community\Application\Controllers\AdministrationController;
use Johncms\Modules\Community\Application\Controllers\CommunityBirthdaysController;
use Johncms\Modules\Community\Application\Controllers\CommunityIndexController;
use Johncms\Modules\Community\Application\Controllers\CommunitySearchController;
use Johncms\Modules\Community\Application\Controllers\CommunityTopController;
use Johncms\Modules\Community\Application\Controllers\CommunityUsersController;
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
use Johncms\Modules\Guestbook\Application\Controllers\GuestbookController;
use Johncms\Modules\News\Application\Controllers\Admin\AdminArticleController;
use Johncms\Modules\News\Application\Controllers\Admin\AdminController;
use Johncms\Modules\News\Application\Controllers\Admin\AdminSectionController;
use Johncms\Modules\News\Application\Controllers\ArticleController;
use Johncms\Modules\News\Application\Controllers\CommentsController;
use Johncms\Modules\News\Application\Controllers\SearchController;
use Johncms\Modules\News\Application\Controllers\SectionController;
use Johncms\Modules\News\Application\Controllers\VoteController;
use Johncms\Router\RouteCollection;
use Johncms\System\Users\User;

return static function (RouteCollection $router, User $user): void {
    $router->get('/', \Johncms\Modules\Homepage\Controllers\HomepageController::class);
    $router->get('/rss', 'modules/rss/index.php');
    $router->map(['GET', 'POST'], '/album/{action}', 'modules/album/index.php')->defaults(['action' => null]);
    $router->get('/community', CommunityIndexController::class);
    $router->get('/community/administration', AdministrationController::class);
    $router->get('/community/birthdays', CommunityBirthdaysController::class);
    $router->get('/community/search', CommunitySearchController::class);
    $router->get('/community/top/{mod}', CommunityTopController::class)->defaults(['mod' => null]);
    $router->get('/community/users', CommunityUsersController::class);
    $router->map(['GET', 'POST'], '/downloads', 'modules/downloads/index.php');
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

    $router->map(['GET', 'POST'], '/guestbook', GuestbookController::class);
    $router->map(['GET', 'POST'], '/guestbook/ga', \Johncms\Modules\Guestbook\Application\Controllers\SwitchTypeController::class);
    if ($user->isValid()) {
        $router->map(['GET', 'POST'], '/guestbook/upload_file', \Johncms\Modules\Guestbook\Application\Controllers\UploadFileController::class);
    }
    if ($user->isValid() && $user->rights > 0) {
        $router->map(['GET', 'POST'], '/guestbook/edit', \Johncms\Modules\Guestbook\Application\Controllers\EditEntryController::class);
        $router->map(['GET', 'POST'], '/guestbook/delpost', \Johncms\Modules\Guestbook\Application\Controllers\DeleteEntryController::class);
    }
    if ($user->isValid() && $user->rights >= 6) {
        $router->map(['GET', 'POST'], '/guestbook/otvet', \Johncms\Modules\Guestbook\Application\Controllers\ReplyController::class);
    }
    if ($user->isValid() && $user->rights >= 7) {
        $router->map(['GET', 'POST'], '/guestbook/clean', \Johncms\Modules\Guestbook\Application\Controllers\ClearGuestbookController::class);
    }

    $router->map(['GET', 'POST'], '/help', 'modules/help/index.php');
    $router->map(['GET', 'POST'], '/library', 'modules/library/index.php');
    $router->map(['GET', 'POST'], '/language', 'modules/language/index.php');
    $router->map(['GET', 'POST'], '/login', 'modules/login/index.php');
    $router->map(['GET', 'POST'], '/mail', 'modules/mail/index.php');

    $router->map(['GET', 'POST'], '/news/search', [SearchController::class, 'index']);
    $router->map(['GET', 'POST'], '/news/search_tags', [SearchController::class, 'byTags']);
    $router->map(['GET', 'POST'], '/news/add_vote/{article_id:number}/{type_vote:number}', [VoteController::class, 'add']);
    $router->map(['GET', 'POST'], '/news/comments/{article_id:number}', [CommentsController::class, 'index']);
    $router->map(['GET', 'POST'], '/news/comments/add/{article_id:number}', [CommentsController::class, 'add']);
    $router->map(['GET', 'POST'], '/news/comments/del', [CommentsController::class, 'del']);
    if ($user->isValid() && empty($user->ban)) {
        $router->map(['GET', 'POST'], '/news/comments/upload_file', [CommentsController::class, 'loadFile']);
    }

    if ($user->rights >= 9 && $user->isValid()) {
        $router->map(['GET', 'POST'], '/admin/news', [AdminController::class, 'index']);
        $router->map(['GET', 'POST'], '/admin/news/content/{section_id:number}', [AdminController::class, 'section'])->defaults(['section_id' => null]);
        $router->map(['GET', 'POST'], '/admin/news/settings', [AdminController::class, 'settings']);
        $router->map(['GET', 'POST'], '/admin/news/edit_article/{article_id:number}', [AdminArticleController::class, 'edit']);
        $router->map(['GET', 'POST'], '/admin/news/add_article/{section_id:number}', [AdminArticleController::class, 'add'])->defaults(['section_id' => null]);
        $router->map(['GET', 'POST'], '/admin/news/del_article/{article_id:number}', [AdminArticleController::class, 'del']);
        $router->map(['GET', 'POST'], '/admin/news/add_section/{section_id:number}', [AdminSectionController::class, 'add'])->defaults(['section_id' => null]);
        $router->map(['GET', 'POST'], '/admin/news/edit_section/{section_id:number}', [AdminSectionController::class, 'edit']);
        $router->map(['GET', 'POST'], '/admin/news/del_section/{section_id:number}', [AdminSectionController::class, 'del']);
        $router->map(['GET', 'POST'], '/admin/news/upload_file', [AdminArticleController::class, 'loadFile']);
    }

    $router->map(['GET', 'POST'], '/news/{category:path}', [SectionController::class, 'index'])->defaults(['category' => null]);
    $router->map(['GET', 'POST'], '/news/{category:path}/{article_code:slug}.html', [ArticleController::class, 'index']);
    $router->map(['GET', 'POST'], '/news/{article_code:slug}.html', [ArticleController::class, 'index']);

    $router->map(['GET', 'POST'], '/online/{action}', 'modules/online/index.php')->defaults(['action' => null]);
    $router->map(['GET', 'POST'], '/profile/skl.php', 'modules/profile/skl.php');
    $router->map(['GET', 'POST'], '/profile', 'modules/profile/index.php');
    $router->map(['GET', 'POST'], '/redirect', 'modules/redirect/index.php');
    $router->map(['GET', 'POST'], '/registration', 'modules/registration/index.php');

    if ($user->isValid()) {
        $router->map(['GET', 'POST'], '/notifications/{action}', 'modules/notifications/index.php')->defaults(['action' => null]);
    }

    $router->map(['GET', 'POST'], '/admin/login', [UsersController::class, 'login']);
    if ($user->rights >= 6 && $user->isValid()) {
        $router->map(['GET', 'POST'], '/admin/system_check', [SystemCheckController::class, 'index']);
    }
    $router->map(['GET', 'POST'], '/admin/{action}', 'modules/admin/index.php')->defaults(['action' => null]);

    if (is_file(CONFIG_PATH . 'routes.local.php')) {
        /** @psalm-suppress MissingFile */
        require CONFIG_PATH . 'routes.local.php';
    }
};
