<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Middleware;

use Johncms\Http\Request;
use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class NewsAvailabilityMiddleware implements MiddlewareInterface
{
    public function __construct(private NewsCollectionAvailability $availability)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->availability->ready()) {
            $message = d__('news', 'News is unavailable until system:update completes.');
            if ($request->isXmlHttpRequest() || str_contains($request->headers->get('Accept', ''), 'application/json')) {
                return new JsonResponse(['message' => $message], 503, ['Retry-After' => '300']);
            }
            return new Response($message, 503, ['Retry-After' => '300', 'Content-Type' => 'text/plain; charset=UTF-8']);
        }
        return $next($request);
    }
}
