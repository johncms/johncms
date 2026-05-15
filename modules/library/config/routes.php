<?php

declare(strict_types=1);

use Johncms\Modules\Library\Application\Controllers\ArticleCommentsController;
use Johncms\Modules\Library\Application\Controllers\CreateArticleController;
use Johncms\Modules\Library\Application\Controllers\CreateSectionController;
use Johncms\Modules\Library\Application\Controllers\DeleteArticleController;
use Johncms\Modules\Library\Application\Controllers\DeleteArticleImageController;
use Johncms\Modules\Library\Application\Controllers\DeleteSectionController;
use Johncms\Modules\Library\Application\Controllers\DownloadArticleController;
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
        $r->get('/library/article/{id:number}/download/{type}', DownloadArticleController::class)->name('library.article.download');
        $r->get('/library/article/{id:number}/delete', DeleteArticleController::class)->name('library.article.delete');
        $r->get('/library/article/{id:number}/image/delete', DeleteArticleImageController::class)->name('library.article.image.delete');
        $r->map(['GET', 'POST'], '/library/section/{id:number}/delete', DeleteSectionController::class)->name('library.section.delete');

        $r->map(['GET', 'POST'], '/library', 'modules/library/index.php')->name('library.index');
    })
        ->addMiddleware(LibraryAccessMiddleware::class);
};
