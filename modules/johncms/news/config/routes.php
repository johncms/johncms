<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Auth\Middlewares\AuthorizedUserMiddleware;
use Johncms\Middlewares\AuthMiddleware;
use Johncms\News\Controllers\Admin\AdminArticleController;
use Johncms\News\Controllers\Admin\AdminController;
use Johncms\News\Controllers\Admin\AdminSectionController;
use Johncms\News\Controllers\ArticleController;
use Johncms\News\Controllers\CommentsController;
use Johncms\News\Controllers\SearchController;
use Johncms\News\Controllers\SectionController;
use Johncms\News\Controllers\VoteController;
use League\Route\RouteGroup;
use League\Route\Router;

/**
 * @psalm-suppress UndefinedInterfaceMethod
 */
return function (Router $router) {
    $router->group('/news', function (RouteGroup $route) {
        $route->get('/search/', [SearchController::class, 'index'])->setName('news.search');
        $route->get('/search_tags/', [SearchController::class, 'byTags'])->setName('news.searchByTags');
        $route->post('/add_vote/{article_id:\d+}/{type_vote:\d}/', [VoteController::class, 'add'])->setName('news.addVote');
        $route->get('/comments/{article_id:\d+}/', [CommentsController::class, 'index'])->setName('news.comments');
        $route->post('/comments/add/{article_id:\d+}/', [CommentsController::class, 'add'])->setName('news.comments.add');
        $route->post('/comments/del/', [CommentsController::class, 'del'])->setName('news.comments.del');
        $route->post('/comments/upload_file[/]', [CommentsController::class, 'loadFile'])
            ->setName('news.uploadFile')
            ->lazyMiddleware(AuthorizedUserMiddleware::class);

        $route->get('/[{category:[\w/+-]+}]', [SectionController::class, 'index'])->setName('news.section');
        $route->get('/{category:[\w/+-]+}/{article_code:[\w.+-]+}.html', [ArticleController::class, 'index'])->setName('news.sectionArticle');
        $route->get('/{article_code:[\w.+-]+}.html', [ArticleController::class, 'index'])->setName('news.article');
    });

    $router->group('/admin/news/', function (RouteGroup $route) {
        $route->get('/', [AdminController::class, 'index'])->setName('news.admin.index');
        $route->get('/content/[{section_id:\d+}[/]]', [AdminController::class, 'section'])->setName('news.admin.section');
        $route->get('/settings/', [AdminController::class, 'settings'])->setName('news.admin.settings');

        // Articles
        $route->get('/edit_article/{article_id:\d+}[/]', [AdminArticleController::class, 'edit'])->setName('news.admin.article.edit');
        $route->get('/add_article/[{section_id:\d+}[/]]', [AdminArticleController::class, 'add'])->setName('news.admin.article.add');
        $route->get('/del_article/{article_id:\d+}[/]', [AdminArticleController::class, 'del'])->setName('news.admin.article.del');

        // Sections
        $route->get('/add_section/[{section_id:\d+}[/]]', [AdminSectionController::class, 'add'])->setName('news.admin.sections.add');
        $route->get('/edit_section/{section_id:\d+}[/]', [AdminSectionController::class, 'edit'])->setName('news.admin.sections.edit');
        $route->get('/del_section/{section_id:\d+}[/]', [AdminSectionController::class, 'del'])->setName('news.admin.sections.del');
        $route->post('/upload_file[/]', [AdminSectionController::class, 'loadFile'])->setName('news.admin.sections.loadFile');
    })->middleware(new AuthMiddleware(['admin']));
};
