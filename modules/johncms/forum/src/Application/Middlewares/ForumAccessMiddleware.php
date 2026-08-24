<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Middlewares;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;

final readonly class ForumAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EnsureForumAccessUseCase $ensureForumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private RendererInterface $renderer,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        try {
            $this->ensureForumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->response($this->renderer, $exception);
        }

        return $next($request);
    }
}
