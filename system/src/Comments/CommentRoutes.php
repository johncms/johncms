<?php

declare(strict_types=1);

namespace Johncms\Comments;

use Johncms\Comments\Controllers\CommentsController;
use Johncms\Http\Middleware\RequireConfirmationMiddleware;
use Johncms\Router\RouteCollection;

/**
 * The routes of a discussion, declared by the module under the address it already had.
 *
 *     CommentRoutes::register($router, '/downloads/comments/{id:number}', 'downloads', 'downloads.comments');
 *
 * The path names the object `id`; the routes the controller needs hang off it.
 */
final class CommentRoutes
{
    public static function register(RouteCollection $router, string $path, string $subject, string $name): void
    {
        $defaults = ['subject' => $subject];

        $router->map(['GET', 'POST'], $path, [CommentsController::class, 'index'])->name($name)->defaults($defaults);
        $router->get($path . '/comment/{comment:number}', [CommentsController::class, 'permalink'])
            ->name($name . '.comment')
            ->defaults($defaults);
        $router->map(['GET', 'POST'], $path . '/edit/{comment:number}', [CommentsController::class, 'edit'])
            ->name($name . '.edit')
            ->defaults($defaults);
        $router->post($path . '/delete', [CommentsController::class, 'delete'])
            ->name($name . '.delete')
            ->defaults($defaults)
            ->addMiddleware(RequireConfirmationMiddleware::class);
        $router->post($path . '/delete-author', [CommentsController::class, 'deleteAuthor'])
            ->name($name . '.delete_author')
            ->defaults($defaults)
            ->addMiddleware(RequireConfirmationMiddleware::class);
        $router->post($path . '/upload', [CommentsController::class, 'upload'])
            ->name($name . '.upload')
            ->defaults($defaults);
        $router->post($path . '/edit/{comment:number}/upload', [CommentsController::class, 'uploadForEdit'])
            ->name($name . '.edit_upload')
            ->defaults($defaults);
    }
}
