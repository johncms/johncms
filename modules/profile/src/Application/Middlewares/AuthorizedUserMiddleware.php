<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Johncms\System\Http\Request;
use Johncms\Users\User;

final readonly class AuthorizedUserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        if (! $this->user->isValid()) {
            pageNotFound();
        }

        return $next($request);
    }
}
