<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Middlewares;

use Johncms\Exceptions\PageNotFoundException;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;
use Johncms\System\Users\User;

final readonly class GuestbookReplyAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private User $user
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->user->isValid() || $this->user->rights < 6) {
            throw new PageNotFoundException();
        }

        return $next($request);
    }
}
