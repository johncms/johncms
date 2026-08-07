<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Middlewares;

use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadsAdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $currentUser,
        private RendererInterface $renderer,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->currentUser->rights < 6 && $this->currentUser->rights !== 4) {
            return new Response(
                $this->renderer->render('@theme/pages/result.twig', [
                    'title'         => __('Downloads'),
                    'type'          => 'alert-danger',
                    'message'       => __('Not found'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        return $next($request);
    }
}
