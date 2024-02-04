<?php

declare(strict_types=1);

use Johncms\News\Controllers\Admin\AdminArticleController;
use Johncms\News\Controllers\Admin\AdminController;
use Johncms\News\Controllers\Admin\AdminSectionController;
use Johncms\News\Controllers\ArticleController;
use Johncms\News\Controllers\CommentsController;
use Johncms\News\Controllers\SearchController;
use Johncms\News\Controllers\SectionController;
use Johncms\News\Controllers\VoteController;
use Johncms\Router\RouteCollection;
use Johncms\Users\Middlewares\AuthorizedUserMiddleware;
use Johncms\Users\Middlewares\HasRoleMiddleware;

return function (RouteCollection $router) {
    // TODO: Add pattern
    // $router->addPatternMatcher('newsSlug', '[\w.+-]+');
    // $router->addPatternMatcher('sectionPath', '[\w/+-]+');

    $router->group('/news', function (RouteCollection $route) {
        $route->get('/search/', [SearchController::class, 'index'])->setName('news.search');
        $route->get('/search_tags/', [SearchController::class, 'byTags'])->setName('news.searchByTags');
        $route->post('/add_vote/{article_id:number}/{type_vote:number}/', [VoteController::class, 'add'])->setName('news.addVote');
        $route->get('/comments/{article_id:number}/', [CommentsController::class, 'index'])->setName('news.comments');
        $route->post('/comments/add/{article_id:number}/', [CommentsController::class, 'add'])->setName('news.comments.add');
        $route->post('/comments/del/', [CommentsController::class, 'del'])->setName('news.comments.del');
        $route->post('/comments/upload_file/', [CommentsController::class, 'loadFile'])
            ->setName('news.uploadFile')
            ->addMiddleware(AuthorizedUserMiddleware::class);

        $route->get('/[{category:sectionPath}]', [SectionController::class, 'index'])->setName('news.section');
        $route->get('/{category:sectionPath}/{article_code:newsSlug}.html', [ArticleController::class, 'index'])->setName('news.sectionArticle');
        $route->get('/{article_code:newsSlug}.html', [ArticleController::class, 'index'])->setName('news.article');
    });

    $router->group('/admin/news', function (RouteCollection $route) {
        $route->get('/', [AdminController::class, 'index'])->setName('news.admin.index');
        // TODO: Change optional
        $route->get('/content/[{section_id:number}/]', [AdminController::class, 'section'])->setName('news.admin.section');
        $route->get('/settings/', [AdminController::class, 'settings'])->setName('news.admin.settings');
        $route->post('/settings/', [AdminController::class, 'settings'])->setName('news.admin.settingsStore');

        // Articles
        $route->get('/edit_article/{article_id:number}/', [AdminArticleController::class, 'edit'])->setName('news.admin.article.edit');
        $route->post('/edit_article/{article_id:number}/', [AdminArticleController::class, 'edit'])->setName('news.admin.article.editStore');
        $route->get('/add_article/[{section_id:number}/]', [AdminArticleController::class, 'add'])->setName('news.admin.article.add');
        $route->post('/add_article/[{section_id:number}/]', [AdminArticleController::class, 'add'])->setName('news.admin.article.addStore');
        $route->get('/del_article/{article_id:number}/', [AdminArticleController::class, 'del'])->setName('news.admin.article.del');
        $route->post('/del_article/{article_id:number}/', [AdminArticleController::class, 'del'])->setName('news.admin.article.delStore');

        // Sections
        $route->get('/add_section/[{section_id:number}/]', [AdminSectionController::class, 'add'])->setName('news.admin.sections.add');
        $route->post('/add_section/[{section_id:number}/]', [AdminSectionController::class, 'add'])->setName('news.admin.sections.add_store');
        $route->get('/edit_section/{section_id:number}/', [AdminSectionController::class, 'edit'])->setName('news.admin.sections.edit');
        $route->post('/edit_section/{section_id:number}/', [AdminSectionController::class, 'edit'])->setName('news.admin.sections.edit_store');
        $route->get('/del_section/{section_id:number}/', [AdminSectionController::class, 'del'])->setName('news.admin.sections.del');
        $route->post('/del_section/{section_id:number}/', [AdminSectionController::class, 'del'])->setName('news.admin.sections.del_store');

        // File uploader
        $route->post('/upload_file/', [AdminArticleController::class, 'loadFile'])->setName('news.admin.sections.loadFile');
    })->addMiddleware(new HasRoleMiddleware('admin'));
};
