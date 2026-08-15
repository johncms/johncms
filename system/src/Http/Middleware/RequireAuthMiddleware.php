<?php

declare(strict_types=1);

namespace Johncms\Http\Middleware;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * A route only a signed-in visitor may reach, whatever their roles.
 *
 * The counterpart of RequirePermissionMiddleware for the routes that ask for nothing beyond a
 * session: writing a post, uploading a file to one. It takes no parameter, so unlike a
 * permission it is added to the route as a middleware and needs no attribute.
 */
final readonly class RequireAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->currentUser->isGuest()) {
            redirect('/login');
        }

        return $next($request);
    }
}
