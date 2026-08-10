<?php

declare(strict_types=1);

namespace Johncms\Http\Middleware;

use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Request;
use Johncms\Http\RequestPathNormalizer;
use Johncms\Router\MiddlewareInterface;
use Johncms\Security\Csrf;
use Johncms\Security\CsrfExemptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects a request with an unsafe method that does not carry the CSRF token.
 *
 * Authenticity of a request is not validation of user input, so the check belongs to the
 * pipeline rather than to a rule every controller has to remember to declare. It runs right
 * after TrimStringsMiddleware and before the middlewares of the route, so a forged request
 * never reaches the logic of a module; the module context is already entered by then, which
 * is what lets the error page answer in the language of the page.
 *
 * A route opts out with Route::withoutCsrf(); config/csrf.php can exempt paths whose routes
 * are not ours to edit.
 */
final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public const HEADER = 'X-CSRF-Token';
    public const FIELD = 'csrf_token';

    /** Methods that may change state, and therefore must prove the request was meant. */
    private const UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private Csrf $csrf,
        private CsrfExemptions $exemptions,
        private RequestPathNormalizer $pathNormalizer,
        private ExceptionResponseFactory $exceptionResponses,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (! $this->requiresToken($request)) {
            return $next($request);
        }

        if ($this->hasValidToken($request)) {
            return $next($request);
        }

        $this->logger->warning('The CSRF token of the request is missing or invalid', [
            'url'     => $request->getRequestUri(),
            'method'  => $request->getMethod(),
            'referer' => $request->headers->get('Referer'),
        ]);

        return $this->exceptionResponses->csrfTokenMismatch($this->wantsJson($request));
    }

    private function requiresToken(Request $request): bool
    {
        if (! in_array($request->getMethod(), self::UNSAFE_METHODS, true)) {
            return false;
        }

        return ! $this->exemptions->exempts($this->pathNormalizer->normalize($request));
    }

    private function hasValidToken(Request $request): bool
    {
        $token = $request->request->get(self::FIELD) ?? $request->headers->get(self::HEADER);

        if (! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($this->csrf->getToken(), $token);
    }

    /**
     * The axios defaults of the theme send X-Requested-With on every request, which is what the
     * components posting JSON are recognized by; Accept covers a client that sets neither.
     */
    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains($request->headers->get('Accept', ''), 'application/json');
    }
}
