<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Johncms\Exceptions\HttpRedirectException;
use Johncms\Exceptions\MethodNotAllowedException;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Turns the control-flow exceptions of the HTTP layer into responses.
 *
 * Lives outside the front controller on purpose: the kernel introduced later reuses it as is.
 */
final readonly class ExceptionResponseFactory
{
    public function __construct(private Render $render)
    {
    }

    public function fromRedirect(HttpRedirectException $exception): RedirectResponse
    {
        return new RedirectResponse($exception->getUrl(), $exception->getStatus());
    }

    public function fromMethodNotAllowed(MethodNotAllowedException $exception): Response
    {
        $allowedMethods = $exception->getAllowedMethods();

        return new Response(
            d__('system', 'Method Not Allowed'),
            Response::HTTP_METHOD_NOT_ALLOWED,
            array_filter(
                [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                    'Allow'        => implode(', ', $allowedMethods),
                ]
            )
        );
    }

    public function fromPageNotFound(PageNotFoundException $exception): Response
    {
        $title = $exception->getTitle();
        $message = $exception->getMessage();

        // The default translation domain is the one of the module that is handling the request,
        // so the system domain has to be named explicitly here.
        return new Response(
            $this->render->render(
                $exception->getTemplate(),
                [
                    'title'   => $title !== ''
                        ? $title
                        : d__('system', 'ERROR: 404 Not Found'),
                    'message' => $message !== ''
                        ? $message
                        : d__('system', 'You are looking for something that doesn\'t exist or may have moved'),
                ]
            ),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * A malformed request: an array where a scalar parameter is expected (?id[]=1), or a Host
     * header that did not pass the trusted-host patterns. Both used to reach GlobalErrorHandler
     * and answer 500 with a log entry per request, which let an unauthenticated client fill the
     * log by looping requests.
     */
    public function badRequest(): Response
    {
        return new Response(
            d__('system', 'Bad Request'),
            Response::HTTP_BAD_REQUEST,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    /**
     * The details are shown under exactly the same rule the global error handler applies, so an
     * unhandled failure does not start leaking stack traces just because it happened inside the
     * kernel (DEBUG is true in the shipped config/constants.php).
     */
    public function internalServerError(Throwable $throwable, bool $showDetails): Response
    {
        return new Response(
            $showDetails ? sprintf('<pre>%s</pre>', htmlspecialchars((string) $throwable)) : 'Internal Server Error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
