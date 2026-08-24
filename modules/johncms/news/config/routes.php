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
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->map(['GET', 'POST'], '/news/search', [SearchController::class, 'index'])->name('news.search');
    $router->map(['GET', 'POST'], '/news/search_tags', [SearchController::class, 'byTags'])->name('news.search_tags');
    $router->map(['GET', 'POST'], '/news/add_vote/{article_id:number}/{type_vote:number}', [VoteController::class, 'add'])->name('news.add_vote');
    $router->map(['GET', 'POST'], '/news/comments/{article_id:number}', [CommentsController::class, 'index'])->name('news.comments');
    $router->map(['GET', 'POST'], '/news/comments/add/{article_id:number}', [CommentsController::class, 'add'])->name('news.comments_add');
    $router->map(['GET', 'POST'], '/news/comments/del', [CommentsController::class, 'del'])->name('news.comments_delete');
    // Signing in is not enough: the permission is what a ban takes away, and uploading a picture
    // for a comment is worth exactly as much as writing one.
    $router->map(['GET', 'POST'], '/news/comments/upload_file', [CommentsController::class, 'loadFile'])
        ->name('news.comments_upload_file')
        ->permission(NewsPermissions::COMMENTS_POST);

    $admin = $router->group('', function (RouteCollection $router): void {
        $router->map(['GET', 'POST'], '/admin/news', [AdminController::class, 'index'])->name('news.admin.index');
        $router->map(['GET', 'POST'], '/admin/news/content/{section_id:number}', [AdminController::class, 'section'])->name('news.admin.section')->defaults(['section_id' => null]);
        $router->map(['GET', 'POST'], '/admin/news/settings', [AdminController::class, 'settings'])->name('news.admin.settings');
        $router->map(['GET', 'POST'], '/admin/news/edit_article/{article_id:number}', [AdminArticleController::class, 'edit'])->name('news.admin.edit_article');
        $router->map(['GET', 'POST'], '/admin/news/add_article/{section_id:number}', [AdminArticleController::class, 'add'])->name('news.admin.add_article')->defaults(['section_id' => null]);
        $router->map(['GET', 'POST'], '/admin/news/del_article/{article_id:number}', [AdminArticleController::class, 'del'])->name('news.admin.delete_article');
        $router->map(['GET', 'POST'], '/admin/news/add_section/{section_id:number}', [AdminSectionController::class, 'add'])->name('news.admin.add_section')->defaults(['section_id' => null]);
        $router->map(['GET', 'POST'], '/admin/news/edit_section/{section_id:number}', [AdminSectionController::class, 'edit'])->name('news.admin.edit_section');
        $router->map(['GET', 'POST'], '/admin/news/del_section/{section_id:number}', [AdminSectionController::class, 'del'])->name('news.admin.delete_section');
        $router->map(['GET', 'POST'], '/admin/news/upload_file', [AdminArticleController::class, 'loadFile'])->name('news.admin.upload_file');
    });
    $admin->permission(NewsPermissions::MANAGE);

    $router->map(['GET', 'POST'], '/news/{category:path}', [SectionController::class, 'index'])->name('news.section')->defaults(['category' => null]);
    $router->map(['GET', 'POST'], '/news/{category:path}/{article_code:slug}.html', [ArticleController::class, 'index'])->name('news.article');
    $router->map(['GET', 'POST'], '/news/{article_code:slug}.html', [ArticleController::class, 'index'])->name('news.article_root');
};
