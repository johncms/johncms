<?php

declare(strict_types=1);

use Johncms\Modules\Library\Application\Controllers\ArticleCommentsController;
use Johncms\Modules\Library\Application\Controllers\ArticleController;
use Johncms\Modules\Library\Application\Controllers\LibraryIndexController;
use Johncms\Modules\Library\Application\Controllers\LibraryPathController;
use Johncms\Modules\Library\Application\Controllers\MoveSectionController;
use Johncms\Modules\Library\Application\Controllers\SectionController;
use Johncms\Modules\Library\Application\Controllers\PremodController;
use Johncms\Modules\Library\Application\Controllers\LatestCommentsController;
use Johncms\Modules\Library\Application\Controllers\TagsController;
use Johncms\Modules\Library\Application\Controllers\CreateArticleController;
use Johncms\Modules\Library\Application\Controllers\CreateSectionController;
use Johncms\Modules\Library\Application\Controllers\DeleteArticleController;
use Johncms\Modules\Library\Application\Controllers\DeleteArticleImageController;
use Johncms\Modules\Library\Application\Controllers\DeleteSectionController;
use Johncms\Modules\Library\Application\Controllers\DownloadArticleController;
use Johncms\Modules\Library\Application\Controllers\EditArticleController;
use Johncms\Modules\Library\Application\Controllers\EditSectionController;
use Johncms\Modules\Library\Application\Controllers\NewArticlesController;
use Johncms\Modules\Library\Application\Controllers\SearchController;
use Johncms\Modules\Library\Application\Controllers\TopController;
use Johncms\Modules\Library\Application\Middlewares\LibraryAccessMiddleware;
use Johncms\Router\RouteCollection;

return static function (RouteCollection $router): void {
    $router->group('', function (RouteCollection $r): void {
        $r->get('/library/top', TopController::class)->name('library.top');
        $r->get('/library/new', NewArticlesController::class)->name('library.new');
        $r->get('/library/search', SearchController::class)->name('library.search');
        $r->map(['GET', 'POST'], '/library/article/create', CreateArticleController::class)->name('library.article.create');
        $r->map(['GET', 'POST'], '/library/section/create', CreateSectionController::class)->name('library.section.create');
        $r->map(['GET', 'POST'], '/library/article/{id:number}/comments', ArticleCommentsController::class)->name('library.article.comments');
        $r->get('/library/premod', PremodController::class)->name('library.premod');
        $r->get('/library/tags', TagsController::class)->name('library.tags');
        $r->get('/library/latest-comments', LatestCommentsController::class)->name('library.latest-comments');
        $r->get('/library/section/{parentId:number}/move/{direction}/{positionIndex:number}', MoveSectionController::class)->name('library.section.move');
        $r->get('/library/article/{id:number}/download/{type}', DownloadArticleController::class)->name('library.article.download');
        $r->map(['GET', 'POST'], '/library/article/{id:number}/edit', EditArticleController::class)->name('library.article.edit');
        $r->map(['GET', 'POST'], '/library/section/{id:number}/edit', EditSectionController::class)->name('library.section.edit');
        $r->get('/library/article/{id:number}/delete', DeleteArticleController::class)->name('library.article.delete');
        $r->get('/library/article/{id:number}/image/delete', DeleteArticleImageController::class)->name('library.article.image.delete');
        $r->map(['GET', 'POST'], '/library/section/{id:number}/delete', DeleteSectionController::class)->name('library.section.delete');

        $r->get('/library', LibraryIndexController::class)->name('library.index');
        $r->map(['GET', 'POST'], '/library/{libraryPath}', LibraryPathController::class)
            ->name('library.path')
            ->requirements(['libraryPath' => '[a-z0-9\\-/]+']);
    })
        ->addMiddleware(LibraryAccessMiddleware::class);
};
