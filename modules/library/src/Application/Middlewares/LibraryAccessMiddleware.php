<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Middlewares;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Router\MiddlewareInterface;
use Johncms\View\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class LibraryAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private RendererInterface $renderer,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $error = '';

        if (! $this->accessChecker->allows(LibraryPermissions::VIEW)) {
            // A guest is told to sign in and everybody else that the library is closed; which of
            // the two it is depends on the user role, and only the first is actionable.
            $error = $this->currentUser->isGuest() ? __('Access forbidden') : __('Library is closed');
        }

        if ($error) {
            return new Response(
                $this->renderer->render('@theme/pages/result.twig', [
                    'title'   => __('Library'),
                    'type'    => 'alert-danger',
                    'message' => $error,
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
