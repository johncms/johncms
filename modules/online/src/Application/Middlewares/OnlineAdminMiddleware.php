<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Middlewares;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\Request;
use Johncms\Router\MiddlewareInterface;
use Johncms\View\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class OnlineAdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RendererInterface $renderer,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW)) {
            return new Response(
                $this->renderer->render('@theme/pages/result.twig', [
                    'title'   => __('Online'),
                    'type'    => 'alert-danger',
                    'message' => __('Access denied'),
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
