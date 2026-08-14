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

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Cookies decided on while the request is being handled, attached to the response once it
 * exists.
 *
 * Some of them are set by code that runs long before there is a response to put them on: the
 * sign-in cookie is reissued by the authenticator, which runs before routing. Queueing is what
 * lets that code stay free of the response — and keeps it out of setcookie(), which writes to
 * the output directly and cannot be undone, tested or replayed in a worker runtime.
 *
 * Emptied between requests, so a cookie decided on for one visitor can never reach the next.
 */
final class CookieQueue implements ResetInterface
{
    /** @var list<Cookie> */
    private array $cookies = [];

    public function add(Cookie $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    public function applyTo(Response $response): void
    {
        foreach ($this->cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }

        $this->cookies = [];
    }

    /**
     * @return list<Cookie>
     */
    public function all(): array
    {
        return $this->cookies;
    }

    public function reset(): void
    {
        $this->cookies = [];
    }
}
