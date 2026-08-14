<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authentication;

use Johncms\Auth\Identity;
use Johncms\Http\Request;

/**
 * Asks every registered authenticator in turn; the first one that recognises the request wins.
 *
 * The order is the order of registration, and it matters: a request carrying both a cookie and
 * a bearer token must be answered by the token, so the token authenticator is registered first.
 * A request nobody recognises belongs to a guest — that is the answer, not a failure.
 *
 * This chain is also how a test signs in as somebody: the test container registers an
 * authenticator of its own, and no seam has to exist in production code for it.
 */
final readonly class AuthenticatorChain
{
    /**
     * @param iterable<AuthenticatorInterface> $authenticators
     */
    public function __construct(private iterable $authenticators)
    {
    }

    public function authenticate(Request $request): Identity
    {
        foreach ($this->authenticators as $authenticator) {
            $identity = $authenticator->authenticate($request);

            if ($identity !== null) {
                return $identity;
            }
        }

        return Identity::guest();
    }
}
