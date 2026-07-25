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

use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transitional contract of the HTTP layer: a controller action may return a Response, a string
 * or nothing, and it is normalized to a Response here. That way the 221 controllers migrate one
 * at a time instead of in a single commit.
 *
 * A wrapped string keeps the response transparent on purpose — see wrapLegacyOutput().
 */
final readonly class ResponseNormalizer
{
    public function normalize(mixed $result, int $status = Response::HTTP_OK): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result !== null && ! is_string($result)) {
            throw new LogicException(
                sprintf(
                    'A controller action must return a Response, a string or null, %s given.',
                    get_debug_type($result)
                )
            );
        }

        return $this->wrapLegacyOutput($result ?? '', $status);
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
     * here or in the kernel until those controllers build their own Response (stage 2c).
     */
    private function wrapLegacyOutput(string $content, int $status): Response
    {
        $response = new Response($content, $status);

        $response->headers->remove('Cache-Control');

        return $response;
    }
}
