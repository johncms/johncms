<?php

declare(strict_types=1);

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
};
