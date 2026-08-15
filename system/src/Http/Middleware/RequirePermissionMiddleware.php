<?php

declare(strict_types=1);

namespace Johncms\Http\Middleware;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Request;
use Johncms\Router\MiddlewareInterface;
use Johncms\Router\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * The gate of a route that declared a permission with Route::permission().
 *
 * One middleware for every permission there is: which one to ask is an attribute of the matched
 * route, put on the request by the kernel. A middleware is resolved by class name and lives as
 * long as the process, so it cannot take the permission in its constructor — and one class per
 * permission would be a hundred classes saying the same thing.
 *
 * A guest is sent to sign in, because for them the refusal is usually a missing session rather
 * than a missing right. Anybody else gets 403 — where the very existence of the route is the
 * secret, the route asks for 404 instead.
 */
final readonly class RequirePermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ExceptionResponseFactory $exceptionResponses,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $permission = $request->attributes->get(Route::PERMISSION_ATTRIBUTE);

        if (! is_string($permission) || $this->accessChecker->allows($permission)) {
            return $next($request);
        }

        if ($request->attributes->getBoolean(Route::PERMISSION_HIDDEN_ATTRIBUTE)) {
            pageNotFound();
        }

        if ($this->currentUser->isGuest()) {
            redirect('/login');
        }

        return $this->exceptionResponses->forbidden($this->wantsJson($request));
    }

    /**
     * The components of the theme post through axios and read response.data.message; an HTML
     * page would reach them as an unparseable body. Same negotiation the CSRF failure does.
     */
    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains($request->headers->get('Accept', ''), 'application/json');
    }
}
