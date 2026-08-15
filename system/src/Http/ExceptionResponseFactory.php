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
use Johncms\View\RendererInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    public function __construct(
        private RendererInterface $renderer,
        private LoggerInterface $logger,
    ) {
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
        $title = $title !== '' ? $title : d__('system', 'ERROR: 404 Not Found');
        $message = $message !== ''
            ? $message
            : d__('system', 'You are looking for something that doesn\'t exist or may have moved');

        try {
            $body = $this->renderer->render($exception->getTemplate(), ['title' => $title, 'message' => $message]);
        } catch (Throwable $throwable) {
            // A theme is free to override the error page, and a broken override would otherwise
            // turn every 404 into a 500 — the status a crawler and a browser act on is the one
            // worth keeping, so the page degrades to text instead of losing it.
            $this->logger->error(
                sprintf('The 404 template "%s" failed to render', $exception->getTemplate()),
                ['exception' => $throwable]
            );

            $body = $this->plainTextPage($title, $message);
        }

        return new Response($body, Response::HTTP_NOT_FOUND);
    }

    /**
     * A request that failed the CSRF check.
     *
     * The answer is negotiated because the Vue components post through axios and read
     * response.data.message: an HTML page would reach them as an unparseable body and the
     * failure would surface as "something went wrong" instead of the real reason.
     */
    public function csrfTokenMismatch(bool $wantsJson): Response
    {
        $title = d__('system', 'The session has expired');
        $message = d__('system', 'Reload the page and submit the form again.');

        if ($wantsJson) {
            return new JsonResponse(['message' => $title . ' ' . $message], Response::HTTP_FORBIDDEN);
        }

        try {
            $body = $this->renderer->render(
                '@theme/pages/errors/403.twig',
                ['title' => $title, 'message' => $message]
            );
        } catch (Throwable $throwable) {
            // Same reason as the 404 above: a broken theme override must not turn the status the
            // browser acts on into a 500.
            $this->logger->error('The 403 template failed to render', ['exception' => $throwable]);

            $body = $this->plainTextPage($title, $message);
        }

        return new Response($body, Response::HTTP_FORBIDDEN);
    }

    /**
     * A refusal: the visitor is signed in and the route is not theirs to open.
     *
     * Negotiated like the CSRF failure above, and for the same reason — the components of the
     * theme read response.data.message.
     */
    public function forbidden(bool $wantsJson = false, ?string $message = null): Response
    {
        $title = d__('system', 'Access denied');
        $message ??= d__('system', 'You are not allowed to open this page.');

        if ($wantsJson) {
            return new JsonResponse(['message' => $title . ' ' . $message], Response::HTTP_FORBIDDEN);
        }

        try {
            $body = $this->renderer->render(
                '@theme/pages/errors/403.twig',
                ['title' => $title, 'message' => $message]
            );
        } catch (Throwable $throwable) {
            $this->logger->error('The 403 template failed to render', ['exception' => $throwable]);

            $body = $this->plainTextPage($title, $message);
        }

        return new Response($body, Response::HTTP_FORBIDDEN);
    }

    private function plainTextPage(string $title, string $message): string
    {
        return sprintf(
            "<!doctype html><html lang=\"en\"><head><meta charset=\"utf-8\"><title>%s</title></head>"
            . "<body><h1>%s</h1><p>%s</p></body></html>",
            htmlspecialchars($title),
            htmlspecialchars($title),
            htmlspecialchars($message)
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
