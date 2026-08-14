<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The identity of the request being served, resolved once and shared for the rest of the cycle.
 *
 * Lazy on purpose: a page that never asks who the visitor is pays for no authentication. The
 * resolved identity is cached for the request and dropped by reset() before the next one, which
 * is what keeps one visitor's identity out of the next answer in a long-running runtime.
 *
 * There is no setter. Replacing the identity is done by registering an authenticator — that is
 * the extension point, and it is also how tests sign in as somebody, so production code needs
 * no seam for them.
 */
final class CurrentUser implements ResetInterface
{
    private ?Identity $identity = null;

    public function __construct(
        private readonly AuthenticatorChain $authenticators,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function identity(): Identity
    {
        if ($this->identity === null) {
            $this->identity = $this->resolve();
        }

        return $this->identity;
    }

    public function id(): int
    {
        return $this->identity()->userId;
    }

    public function isGuest(): bool
    {
        return $this->identity()->isGuest();
    }

    public function reset(): void
    {
        $this->identity = null;
    }

    private function resolve(): Identity
    {
        $request = $this->requestStack->getCurrentRequest();

        // Console commands, cron and the scheduler run without a request: nobody is signed in,
        // and asking for the current user there must not be an error — it is a guest.
        if (! $request instanceof Request) {
            return Identity::guest();
        }

        return $this->authenticators->authenticate($request);
    }
}
