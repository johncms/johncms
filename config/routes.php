<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use FastRoute\RouteCollector;
use Johncms\Modules\Admin\Application\Controllers\System\SystemCheckController;
use Johncms\Modules\Admin\Application\Controllers\Users\UsersController;
use Johncms\Modules\Guestbook\Application\Controllers\GuestbookController;
use Johncms\Modules\News\Application\Controllers\Admin\AdminArticleController;
use Johncms\Modules\News\Application\Controllers\Admin\AdminController;
use Johncms\Modules\News\Application\Controllers\Admin\AdminSectionController;
use Johncms\Modules\News\Application\Controllers\ArticleController;
use Johncms\Modules\News\Application\Controllers\CommentsController;
use Johncms\Modules\News\Application\Controllers\SearchController;
use Johncms\Modules\News\Application\Controllers\SectionController;
use Johncms\Modules\News\Application\Controllers\VoteController;
use Johncms\Modules\Forum\Application\Controllers\AddFileController;
use Johncms\Modules\Forum\Application\Controllers\AddVoteController;
use Johncms\Modules\Forum\Application\Controllers\ChangeTopicController;
use Johncms\Modules\Forum\Application\Controllers\CuratorsController;
use Johncms\Modules\Forum\Application\Controllers\ClearFilterByAuthorController;
use Johncms\Modules\Forum\Application\Controllers\BulkDeletePostsController;
use Johncms\Modules\Forum\Application\Controllers\EditVoteController;
use Johncms\Modules\Forum\Application\Controllers\DeleteVoteController;
use Johncms\Modules\Forum\Application\Controllers\CloseTopicController;
use Johncms\Modules\Forum\Application\Controllers\DeleteTopicController;
use Johncms\Modules\Forum\Application\Controllers\DeletePostController;
use Johncms\Modules\Forum\Application\Controllers\DeletePostFileController;
use Johncms\Modules\Forum\Application\Controllers\EditPostController;
use Johncms\Modules\Forum\Application\Controllers\DownloadFileController;
use Johncms\Modules\Forum\Application\Controllers\ForumFilesController;
use Johncms\Modules\Forum\Application\Controllers\ForumSearchController;
use Johncms\Modules\Forum\Application\Controllers\FilterByAuthorController;
use Johncms\Modules\Forum\Application\Controllers\LatestTopicsController;
use Johncms\Modules\Forum\Application\Controllers\MarkAllTopicsReadController;
use Johncms\Modules\Forum\Application\Controllers\PollVotersController;
use Johncms\Modules\Forum\Application\Controllers\SetFilterByAuthorController;
use Johncms\Modules\Forum\Application\Controllers\ShowPostController;
use Johncms\Modules\Forum\Application\Controllers\NewTopicController;
use Johncms\Modules\Forum\Application\Controllers\MoveTopicController;
use Johncms\Modules\Forum\Application\Controllers\PinTopicController;
use Johncms\Modules\Forum\Application\Controllers\NewMessageController;
use Johncms\Modules\Forum\Application\Controllers\ReplyMessageController;
use Johncms\Modules\Forum\Application\Controllers\RestorePostController;
use Johncms\Modules\Forum\Application\Controllers\RestoreTopicController;
use Johncms\Modules\Forum\Application\Controllers\SubmitVoteController;
use Johncms\Modules\Forum\Application\Controllers\TopicsPeriodController;
use Johncms\Modules\Forum\Application\Controllers\UnreadTopicsController;
use Johncms\Modules\Forum\Application\Controllers\ViewForumVisitorsController;
use Johncms\Modules\Forum\Application\Controllers\ViewTopicVisitorsController;
use Johncms\Modules\Forum\Application\Controllers\ClearForumSearchHistoryController;
use Johncms\System\Users\User;

return static function (RouteCollector $map, User $user) {
    $map->get('/', \Johncms\Modules\Homepage\Controllers\HomepageController::class);                                           // Home Page
    $map->get('/rss[/]', 'modules/rss/index.php');                                                                             // RSS
    $map->addRoute(['GET', 'POST'], '/album[/[{action}]]', 'modules/album/index.php');                                         // Photo Album
    $map->addRoute(['GET', 'POST'], '/community/[{action}/[{mod}/]]', 'modules/community/index.php');                          // Users community
    $map->addRoute(['GET', 'POST'], '/downloads[/]', 'modules/downloads/index.php');                                           // Downloads
    $map->addRoute(['GET', 'POST'], '/forum[/]', 'modules/forum/index.php');                                                   // Forum
    $map->addRoute(['GET'], '/forum/download-file/{id:\d+}[/]', DownloadFileController::class);
    $map->addRoute(['GET'], '/forum/files[/]', ForumFilesController::class);
    $map->addRoute(['GET'], '/forum/search[/]', ForumSearchController::class);
    $map->addRoute(['GET', 'POST'], '/forum/search/history/clear[/]', ClearForumSearchHistoryController::class);
    $map->addRoute(['GET'], '/forum/filter/{id:\d+}[/]', FilterByAuthorController::class);
    $map->addRoute(['POST'], '/forum/filter/{id:\d+}/clear[/]', ClearFilterByAuthorController::class);
    $map->addRoute(['POST'], '/forum/filter/{id:\d+}/set[/]', SetFilterByAuthorController::class);
    $map->addRoute(['GET'], '/forum/latest-topics[/]', LatestTopicsController::class);
    $map->addRoute(['GET'], '/forum/post/{id:\d+}[/]', ShowPostController::class);
    $map->addRoute(['GET'], '/forum/poll-voters/{id:\d+}[/]', PollVotersController::class);
    $map->addRoute(['GET'], '/forum/visitors[/]', ViewForumVisitorsController::class);
    $map->addRoute(['GET'], '/forum/topic-visitors/{id:\d+}[/]', ViewTopicVisitorsController::class);
    $map->addRoute(['GET'], '/forum/unread[/]', UnreadTopicsController::class);
    $map->addRoute(['POST'], '/forum/unread/mark-read[/]', MarkAllTopicsReadController::class);
    $map->addRoute(['GET', 'POST'], '/forum/topics-period[/]', TopicsPeriodController::class);
    if ($user->isValid()) {
        $map->addRoute(['GET', 'POST'], '/forum/addfile/{id:\d+}[/]', AddFileController::class);
        $map->addRoute(['GET', 'POST'], '/forum/addvote/{id:\d+}[/]', AddVoteController::class);
        $map->addRoute(['GET', 'POST'], '/forum/change-topic/{id:\d+}[/]', ChangeTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/curators/{id:\d+}[/]', CuratorsController::class);
        $map->addRoute(['GET', 'POST'], '/forum/bulk-delete-posts/{id:\d+}[/]', BulkDeletePostsController::class);
        $map->addRoute(['GET', 'POST'], '/forum/editvote/{id:\d+}[/]', EditVoteController::class);
        $map->addRoute(['GET', 'POST'], '/forum/delvote/{id:\d+}[/]', DeleteVoteController::class);
        $map->addRoute(['GET', 'POST'], '/forum/new-topic/{id:\d+}[/]', NewTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/new-message/{id:\d+}[/]', NewMessageController::class);
        $map->addRoute(['GET', 'POST'], '/forum/reply-message/{id:\d+}[/]', ReplyMessageController::class);
        $map->addRoute(['GET', 'POST'], '/forum/edit-post/{id:\d+}[/]', EditPostController::class);
        $map->addRoute(['GET', 'POST'], '/forum/move-topic/{id:\d+}[/]', MoveTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/delete-post/{id:\d+}[/]', DeletePostController::class);
        $map->addRoute(['GET', 'POST'], '/forum/restore-post/{id:\d+}[/]', RestorePostController::class);
        $map->addRoute(['GET', 'POST'], '/forum/delete-post-file/{id:\d+}/{fid:\d+}[/]', DeletePostFileController::class);
        $map->addRoute(['GET', 'POST'], '/forum/close/{id:\d+}[/]', CloseTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/delete-topic/{id:\d+}[/]', DeleteTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/pin-topic/{id:\d+}[/]', PinTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/restore-topic/{id:\d+}[/]', RestoreTopicController::class);
        $map->addRoute(['GET', 'POST'], '/forum/poll-vote/{id:\d+}[/]', SubmitVoteController::class);
    }

    $map->addRoute(['GET', 'POST'], '/guestbook[/]', GuestbookController::class);                // Guestbook, mini-chat
    $map->addRoute(['GET', 'POST'], '/guestbook/ga[/]', \Johncms\Modules\Guestbook\Application\Controllers\SwitchTypeController::class);
    if ($user->isValid()) {
        $map->addRoute(['GET', 'POST'], '/guestbook/upload_file[/]', \Johncms\Modules\Guestbook\Application\Controllers\UploadFileController::class);
    }

    if ($user->isValid() && $user->rights > 0) {
        $map->addRoute(['GET', 'POST'], '/guestbook/edit[/]', \Johncms\Modules\Guestbook\Application\Controllers\EditEntryController::class);
        $map->addRoute(['GET', 'POST'], '/guestbook/delpost[/]', \Johncms\Modules\Guestbook\Application\Controllers\DeleteEntryController::class);
    }
    if ($user->isValid() && $user->rights >= 6) {
        $map->addRoute(['GET', 'POST'], '/guestbook/otvet[/]', \Johncms\Modules\Guestbook\Application\Controllers\ReplyController::class);
    }
    if ($user->isValid() && $user->rights >= 7) {
        $map->addRoute(['GET', 'POST'], '/guestbook/clean[/]', \Johncms\Modules\Guestbook\Application\Controllers\ClearGuestbookController::class);
    }

    $map->addRoute(['GET', 'POST'], '/help[/]', 'modules/help/index.php');                            // Help
    $map->addRoute(['GET', 'POST'], '/library[/]', 'modules/library/index.php');                      // Articles Library
    $map->addRoute(['GET', 'POST'], '/language[/]', 'modules/language/index.php');                    // Language switcher
    $map->addRoute(['GET', 'POST'], '/login[/]', 'modules/login/index.php');                          // Login / Logout
    $map->addRoute(['GET', 'POST'], '/mail[/]', 'modules/mail/index.php');                            // Personal Messages

    $map->addRoute(['GET', 'POST'], '/news/search/', [SearchController::class, 'index']);
    $map->addRoute(['GET', 'POST'], '/news/search_tags/', [SearchController::class, 'byTags']);
    $map->addRoute(['GET', 'POST'], '/news/add_vote/{article_id:\d+}/{type_vote:\d}/', [VoteController::class, 'add']);
    $map->addRoute(['GET', 'POST'], '/news/comments/{article_id:\d+}/', [CommentsController::class, 'index']);
    $map->addRoute(['GET', 'POST'], '/news/comments/add/{article_id:\d+}/', [CommentsController::class, 'add']);
    $map->addRoute(['GET', 'POST'], '/news/comments/del/', [CommentsController::class, 'del']);
    if ($user->isValid() && empty($user->ban)) {
        $map->addRoute(['GET', 'POST'], '/news/comments/upload_file[/]', [CommentsController::class, 'loadFile']);
    }

    if ($user->rights >= 9 && $user->isValid()) {
        $map->addRoute(['GET', 'POST'], '/admin/news/', [AdminController::class, 'index']);
        $map->addRoute(['GET', 'POST'], '/admin/news/content/[{section_id:\d+}[/]]', [AdminController::class, 'section']);
        $map->addRoute(['GET', 'POST'], '/admin/news/settings/', [AdminController::class, 'settings']);
        $map->addRoute(['GET', 'POST'], '/admin/news/edit_article/{article_id:\d+}[/]', [AdminArticleController::class, 'edit']);
        $map->addRoute(['GET', 'POST'], '/admin/news/add_article/[{section_id:\d+}[/]]', [AdminArticleController::class, 'add']);
        $map->addRoute(['GET', 'POST'], '/admin/news/del_article/{article_id:\d+}[/]', [AdminArticleController::class, 'del']);
        $map->addRoute(['GET', 'POST'], '/admin/news/add_section/[{section_id:\d+}[/]]', [AdminSectionController::class, 'add']);
        $map->addRoute(['GET', 'POST'], '/admin/news/edit_section/{section_id:\d+}[/]', [AdminSectionController::class, 'edit']);
        $map->addRoute(['GET', 'POST'], '/admin/news/del_section/{section_id:\d+}[/]', [AdminSectionController::class, 'del']);
        $map->addRoute(['GET', 'POST'], '/admin/news/upload_file[/]', [AdminArticleController::class, 'loadFile']);
    }

    $map->addRoute(['GET', 'POST'], '/news/[{category:[\w/+-]+}]', [SectionController::class, 'index']);
    $map->addRoute(['GET', 'POST'], '/news/{category:[\w/+-]+}/{article_code:[\w.+-]+}.html', [ArticleController::class, 'index']);
    $map->addRoute(['GET', 'POST'], '/news/{article_code:[\w.+-]+}.html', [ArticleController::class, 'index']);

    $map->addRoute(['GET', 'POST'], '/online/[{action}/]', 'modules/online/index.php');               // Online site activity
    $map->addRoute(['GET', 'POST'], '/profile/skl.php', 'modules/profile/skl.php');                   // Restore Password
    $map->addRoute(['GET', 'POST'], '/profile[/]', 'modules/profile/index.php');                      // User Profile
    $map->addRoute(['GET', 'POST'], '/redirect/', 'modules/redirect/index.php');                      // Redirect on link
    $map->addRoute(['GET', 'POST'], '/registration[/]', 'modules/registration/index.php');            // New users registration

    if ($user->isValid()) {
        $map->addRoute(['GET', 'POST'], '/notifications/[{action}/]', 'modules/notifications/index.php');      // Notifications
    }

    $map->addRoute(['GET', 'POST'], '/admin/login[/]', [UsersController::class, 'login']);
    if ($user->rights >= 6 && $user->isValid()) {
        $map->addRoute(['GET', 'POST'], '/admin/system_check[/]', [SystemCheckController::class, 'index']);                      // Administration
    }
    $map->addRoute(['GET', 'POST'], '/admin[/[{action}/]]', 'modules/admin/index.php');                      // Administration

    // Custom routes
    if (is_file(CONFIG_PATH . 'routes.local.php')) {
        /** @psalm-suppress MissingFile */
        require CONFIG_PATH . 'routes.local.php';
    }
};
