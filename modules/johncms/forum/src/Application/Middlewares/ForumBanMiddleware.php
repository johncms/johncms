<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Middlewares;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;

/**
 * Refuses everything that writes in the forum to an account banned from it or from the whole site.
 *
 * It stands in front of the routes of the signed-in visitor: the topics and posts, the changes of a
 * post of their own, the polls, the moderation. Several of them ask for no permission at all, so
 * this is the one place that keeps them shut, and a route added to the list asks it as well.
 */
final readonly class ForumBanMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private RendererInterface $renderer,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $bans = $this->currentUser->user()->ban;

        if (isset($bans[1]) || isset($bans[11])) {
            return $this->forumErrorRenderer->response($this->renderer, new ForumAccessDeniedException());
        }

        return $next($request);
    }
}
