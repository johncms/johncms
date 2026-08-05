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

use Johncms\Http\View\ViewResponse;
use Johncms\View\RendererInterface;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transitional contract of the HTTP layer: a controller action may return a Response, a
 * ViewResponse, a string or nothing, and it is normalized to a Response here. That way the 221
 * controllers migrate one at a time instead of in a single commit.
 *
 * A wrapped string keeps the response transparent on purpose — see wrapLegacyOutput().
 */
final readonly class ResponseNormalizer
{
    /**
     * The renderer arrives as a closure: building it assembles the template environment, and a
     * controller that returns a string or a Response of its own must not pay for it.
     *
     * @param callable(): RendererInterface $renderer
     */
    public function __construct(private mixed $renderer)
    {
    }

    public function normalize(mixed $result, int $status = Response::HTTP_OK): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof ViewResponse) {
            return $this->render($result);
        }

        if ($result !== null && ! is_string($result)) {
            throw new LogicException(
                sprintf(
                    'A controller action must return a Response, a ViewResponse, a string or null, %s given.',
                    get_debug_type($result)
                )
            );
        }

        return $this->wrapLegacyOutput($result ?? '', $status);
    }

    /**
     * A rendered page is HTML by definition, so unlike a legacy body it declares its content type
     * instead of relying on the default of PHP.
     */
    private function render(ViewResponse $view): Response
    {
        $renderer = ($this->renderer)();

        $response = new Response($renderer->render($view->template, $view->data), $view->status);

        // The session sends a Cache-Control header of its own; a second one would be appended to
        // it rather than replace it. See wrapLegacyOutput().
        $response->headers->remove('Cache-Control');
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }

    /**
     * Wraps the body of a controller that has not been migrated to Response yet.
     *
     * Such a controller still sends its own headers with header() and http_response_code(), so the
     * wrapper must not fight them. A fresh Response puts only Cache-Control and Date into its
     * header bag: the Cache-Control is dropped here because sendHeaders() appends it next to the
     * one PHP already sent for the session.
     *
     * Content-Type is deliberately never set. It enters the bag only through Response::prepare(),
     * and sendHeaders() is the one place that sends Content-Type with replace = true — so calling
     * prepare() on a wrapped legacy body would silently turn the application/json of the 16 JSON
     * endpoints and the octet-stream of the library download into text/html. Do not call prepare()
     * here or in the kernel until those controllers build their own Response.
     */
    private function wrapLegacyOutput(string $content, int $status): Response
    {
        $response = new Response($content, $status);

        $response->headers->remove('Cache-Control');

        return $response;
    }
}
