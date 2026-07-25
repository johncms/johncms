<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Middlewares;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Router\MiddlewareInterface;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class ForumAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EnsureForumAccessUseCase $ensureForumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private Render $render,
    ) {
    }

    public function handle(Request $request, callable $next): mixed
    {
        try {
            $this->ensureForumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        return $next($request);
    }
}
