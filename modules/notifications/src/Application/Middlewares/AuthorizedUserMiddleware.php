<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;
use Johncms\System\Users\User;

final readonly class AuthorizedUserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->user->isValid()) {
            pageNotFound();
        }

        return $next($request);
    }
}
