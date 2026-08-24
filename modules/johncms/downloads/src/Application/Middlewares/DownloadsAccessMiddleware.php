<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Middlewares;

use Johncms\Modules\Downloads\Application\Exceptions\DownloadsAccessDeniedException;
use Johncms\Modules\Downloads\Application\Services\DownloadsErrorRenderer;
use Johncms\Modules\Downloads\Application\UseCases\EnsureDownloadsAccessUseCase;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;

final readonly class DownloadsAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EnsureDownloadsAccessUseCase $ensureDownloadsAccessUseCase,
        private DownloadsErrorRenderer $downloadsErrorRenderer,
        private RendererInterface $renderer,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        try {
            $this->ensureDownloadsAccessUseCase->execute();
        } catch (DownloadsAccessDeniedException $exception) {
            return $this->downloadsErrorRenderer->response($this->renderer, $exception);
        }

        return $next($request);
    }
}
