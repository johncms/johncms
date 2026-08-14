<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Session;

/**
 * A session together with the secret that was just handed out for it.
 *
 * The secret exists in this form only here: the row keeps its digest, so the caller either puts
 * it in a cookie now or loses it. That is deliberate — a token that can be read back later is a
 * token a database dump hands out.
 */
final readonly class IssuedSession
{
    public function __construct(
        public AuthSession $session,
        public string $token,
    ) {
    }
}
