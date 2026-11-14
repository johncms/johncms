<?php

declare(strict_types=1);

use Johncms\Comments\CommentRoutes;
use Johncms\Modules\News\Application\Comments\NewsCommentSubject;
use Johncms\Modules\News\Application\Controllers\Admin\NewsSettingsController;
use Johncms\Modules\News\Application\Controllers\ArticleController;
use Johncms\Modules\News\Application\Controllers\SearchController;
use Johncms\Modules\News\Application\Controllers\SectionController;
use Johncms\Modules\News\Application\Controllers\VoteController;
use Johncms\Modules\News\Application\Middleware\NewsAvailabilityMiddleware;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $public = $router->group('', function (RouteCollection $router): void {
        $router->map(['GET', 'POST'], '/news/search', [SearchController::class, 'index'])->name('news.search');
        $router->map(['GET', 'POST'], '/news/search_tags', [SearchController::class, 'byTags'])->name('news.search_tags');
        $router->post('/news/add_vote/{article_id:number}/{type_vote:number}', [VoteController::class, 'add'])->name('news.add_vote');
        // Before the sections: their codes would take `comments/…` for a path of sections.
        CommentRoutes::register($router, '/news/comments/{id:number}', NewsCommentSubject::TYPE, 'news.comments');

        // Section codes may contain dots, so the addresses of articles have to be tried first.
        $sectionPath = ['category' => '[\w/.+-]+'];
        $router->map(['GET', 'POST'], '/news/{category}/{article_code:slug}.html', [ArticleController::class, 'index'])
            ->requirements($sectionPath)
            ->name('news.article');
        $router->map(['GET', 'POST'], '/news/{article_code:slug}.html', [ArticleController::class, 'index'])->name('news.article_root');
        $router->map(['GET', 'POST'], '/news/{category}', [SectionController::class, 'index'])
            ->requirements($sectionPath)
            ->name('news.section')
            ->defaults(['category' => null]);
    });
    $public->addMiddleware(NewsAvailabilityMiddleware::class);

    $admin = $router->group('', function (RouteCollection $router): void {
        $router->get('/admin/news/settings', [NewsSettingsController::class, 'form'])->name('news.admin.settings');
        $router->post('/admin/news/settings', [NewsSettingsController::class, 'save'])->name('news.admin.settings.save');
    });
    $admin->permission(NewsPermissions::MANAGE)->adminArea();
};
