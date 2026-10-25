<?php

declare(strict_types=1);

use Johncms\Modules\News\Application\Controllers\Admin\AdminController;
use Johncms\Modules\News\Application\Controllers\Admin\ArticleFormController;
use Johncms\Modules\News\Application\Controllers\Admin\ArticleUploadController;
use Johncms\Modules\News\Application\Controllers\Admin\DeleteArticleController;
use Johncms\Modules\News\Application\Controllers\Admin\DeleteSectionController;
use Johncms\Modules\News\Application\Controllers\Admin\NewsListController;
use Johncms\Modules\News\Application\Controllers\Admin\SectionFormController;
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
        $router->get('/admin/news', NewsListController::class)->name('news.admin.index');
        $router->map(['GET', 'POST'], '/admin/news/settings', [AdminController::class, 'settings'])->name('news.admin.settings');
        $router->get('/admin/news/articles/new', [ArticleFormController::class, 'create'])->name('news.admin.add_article');
        $router->post('/admin/news/articles/new', [ArticleFormController::class, 'store'])->name('news.admin.add_article.store');
        $router->get('/admin/news/articles/{id:number}/edit', [ArticleFormController::class, 'edit'])->name('news.admin.edit_article');
        $router->post('/admin/news/articles/{id:number}/edit', [ArticleFormController::class, 'update'])->name('news.admin.edit_article.update');
        $router->get('/admin/news/articles/{id:number}/delete', [DeleteArticleController::class, 'index'])->name('news.admin.delete_article');
        $router->post('/admin/news/articles/{id:number}/delete', [DeleteArticleController::class, 'delete'])->name('news.admin.delete_article.run');
        $router->get('/admin/news/sections/new', [SectionFormController::class, 'create'])->name('news.admin.add_section');
        $router->post('/admin/news/sections/new', [SectionFormController::class, 'store'])->name('news.admin.add_section.store');
        $router->get('/admin/news/sections/{id:number}/edit', [SectionFormController::class, 'edit'])->name('news.admin.edit_section');
        $router->post('/admin/news/sections/{id:number}/edit', [SectionFormController::class, 'update'])->name('news.admin.edit_section.update');
        $router->get('/admin/news/sections/{id:number}/delete', [DeleteSectionController::class, 'index'])->name('news.admin.delete_section');
        $router->post('/admin/news/sections/{id:number}/delete', [DeleteSectionController::class, 'delete'])->name('news.admin.delete_section.run');
        $router->post('/admin/news/upload_file', ArticleUploadController::class)->name('news.admin.upload_file');
    });
    $admin->permission(NewsPermissions::MANAGE)->adminArea();

    $router->map(['GET', 'POST'], '/news/{category:path}', [SectionController::class, 'index'])->name('news.section')->defaults(['category' => null]);
    $router->map(['GET', 'POST'], '/news/{category:path}/{article_code:slug}.html', [ArticleController::class, 'index'])->name('news.article');
    $router->map(['GET', 'POST'], '/news/{article_code:slug}.html', [ArticleController::class, 'index'])->name('news.article_root');
};
