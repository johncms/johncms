<?php

declare(strict_types=1);

namespace Johncms\Http\Middleware;

use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Request;
use Johncms\Router\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The gate of a route whose action cannot be undone: it is carried out only when the visitor
 * answered yes in the dialog of the page.
 *
 * The dialog (src/js/confirm.js) puts the confirmed field into the form once the answer is yes.
 * A form sent without it — the script did not load, or broke — is refused here before it reaches
 * the controller: a button that does nothing is better than a removal nobody agreed to. The field
 * guards against a careless submission, not a forged one; that is what the CSRF check is for.
 *
 * Only unsafe methods are checked, so a route that shows a page on GET can take the gate as well.
 */
final readonly class RequireConfirmationMiddleware implements MiddlewareInterface
{
    public const string FIELD = 'confirmed';

    private const array UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private ExceptionResponseFactory $exceptionResponses,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! in_array($request->getMethod(), self::UNSAFE_METHODS, true)) {
            return $next($request);
        }

        if ($request->request->get(self::FIELD) === '1') {
            return $next($request);
        }

        return $this->exceptionResponses->notConfirmed($this->wantsJson($request));
    }

    /**
     * The components of the theme post through axios and read response.data.message; an HTML
     * page would reach them as an unparseable body. Same negotiation the CSRF failure does.
     */
    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains($request->headers->get('Accept', ''), 'application/json');
    }
}
