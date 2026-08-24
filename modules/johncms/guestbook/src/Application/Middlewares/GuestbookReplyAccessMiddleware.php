<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Middlewares;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Http\Request;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;

final readonly class GuestbookReplyAccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->accessChecker->allows(GuestbookPermissions::ENTRY_REPLY)) {
            throw new PageNotFoundException();
        }

        return $next($request);
    }
}
