<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Middlewares;

use Johncms\Auth\CurrentUser;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;

/**
 * The profiles are open to members only. A visitor who is not one is told so with a 403 rather than
 * a 404: the profile exists, and signing in is what opens it.
 */
final readonly class AuthorizedUserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private ExceptionResponseFactory $exceptionResponses,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->currentUser->isValid()) {
            return $this->exceptionResponses->forbidden(message: d__('profile', 'For registered users only'));
        }

        return $next($request);
    }
}
