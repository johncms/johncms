<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Middleware;

use Johncms\Auth\CurrentUser;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Request;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class CollectionContentAccessMiddleware implements MiddlewareInterface
{
    public function __construct(private EnsureCollectionContentAccessUseCase $guard, private CurrentUser $user, private ExceptionResponseFactory $responses)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        try {
            $this->guard->execute((int) $request->attributes->get('collection_id'));
            return $next($request);
        } catch (CollectionAccessDeniedException) {
            if ($this->user->isGuest()) {
                redirect('/login');
            }
            return $this->responses->forbidden($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json'));
        }
    }
}
