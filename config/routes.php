<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Admin\Controllers\System\SystemCheckController;
use Admin\Controllers\Users\UsersController;
use FastRoute\RouteCollector;
use Guestbook\Controllers\GuestbookController;
use Johncms\System\Users\User;
use News\Controllers\Admin\AdminArticleController;
use News\Controllers\Admin\AdminController;
use News\Controllers\Admin\AdminSectionController;
use News\Controllers\ArticleController;
use News\Controllers\CommentsController;
use News\Controllers\SearchController;
use News\Controllers\SectionController;
use News\Controllers\VoteController;

return static function (RouteCollector $map, User $user) {
    $map->get('/', [Homepage\Controllers\HomepageController::class, 'index']);                                // Home Page
    $map->get('/rss[/]', 'modules/rss/index.php');                                                    // RSS
    $map->addRoute(['GET', 'POST'], '/album[/[{action}]]', 'modules/album/index.php');                          // Photo Album
    $map->addRoute(['GET', 'POST'], '/community/[{action}/[{mod}/]]', 'modules/community/index.php'); // Users community
    $map->addRoute(['GET', 'POST'], '/downloads[/]', 'modules/downloads/index.php');                  // Downloads
    $map->addRoute(['GET', 'POST'], '/forum[/]', 'modules/forum/index.php');                          // Forum

    $map->addRoute(['GET', 'POST'], '/guestbook[/]', [GuestbookController::class, 'index']);                // Guestbook, mini-chat
    $map->addRoute(['GET', 'POST'], '/guestbook/ga[/]', [GuestbookController::class, 'switchGuestbookType']);
    if ($user->isValid()) {
        $map->addRoute(['GET', 'POST'], '/guestbook/upload_file[/]', [GuestbookController::class, 'loadFile']);
    }

    if ($user->isValid() && $user->rights > 0) {
        $map->addRoute(['GET', 'POST'], '/guestbook/edit[/]', [GuestbookController::class, 'edit']);
        $map->addRoute(['GET', 'POST'], '/guestbook/delpost[/]', [GuestbookController::class, 'delete']);
    }
    if ($user->isValid() && $user->rights >= 6) {
        $map->addRoute(['GET', 'POST'], '/guestbook/otvet[/]', [GuestbookController::class, 'reply']);
    }
    if ($user->isValid() && $user->rights >= 7) {
        $map->addRoute(['GET', 'POST'], '/guestbook/clean[/]', [GuestbookController::class, 'clean']);
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
