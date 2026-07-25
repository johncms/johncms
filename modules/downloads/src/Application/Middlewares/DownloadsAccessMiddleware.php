<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Middlewares;

use Johncms\Modules\Downloads\Application\Exceptions\DownloadsAccessDeniedException;
use Johncms\Modules\Downloads\Application\Services\DownloadsErrorRenderer;
use Johncms\Modules\Downloads\Application\UseCases\EnsureDownloadsAccessUseCase;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class DownloadsAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EnsureDownloadsAccessUseCase $ensureDownloadsAccessUseCase,
        private DownloadsErrorRenderer $downloadsErrorRenderer,
        private Render $render,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        try {
            $this->ensureDownloadsAccessUseCase->execute();
        } catch (DownloadsAccessDeniedException $exception) {
            return $this->downloadsErrorRenderer->render($this->render, $exception);
        }

        return $next($request);
    }
}
