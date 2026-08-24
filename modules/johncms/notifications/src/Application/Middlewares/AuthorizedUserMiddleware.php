<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Middlewares;

use Johncms\Auth\CurrentUser;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;

final readonly class AuthorizedUserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->currentUser->isValid()) {
            pageNotFound();
        }

        return $next($request);
    }
}
