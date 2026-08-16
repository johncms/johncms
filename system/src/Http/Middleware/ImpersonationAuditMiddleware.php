<?php

declare(strict_types=1);

namespace Johncms\Http\Middleware;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Http\Request;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records what an administrator does while browsing as somebody else.
 *
 * Writing as the user is not forbidden — without it half the complaints could not be reproduced —
 * and it does not give an administrator anything they could not do straight in the database. What
 * it does give them is a record: every request that changes something names the method, the path
 * and who was really behind it.
 *
 * Reads are left alone: they change nothing, and a row per page view would bury the entries that
 * matter.
 */
final readonly class ImpersonationAuditMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private CurrentUser $currentUser,
        private AuthEventLoggerInterface $eventLogger,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $identity = $this->currentUser->identity();

        if ($identity->isImpersonating() && ! in_array($request->getMethod(), self::SAFE_METHODS, true)) {
            $this->eventLogger->log(
                AuthEventType::ImpersonatedAction,
                $identity->userId,
                [
                    'method' => $request->getMethod(),
                    'path'   => $request->getPathInfo(),
                ],
                actorId: $identity->impersonatorId
            );
        }

        return $next($request);
    }
}
