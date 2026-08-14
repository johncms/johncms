<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\Authentication\AuthenticatorInterface;
use Johncms\Auth\Identity;
use Johncms\Http\Request;

/**
 * Signs the test in as whoever it was given, or lets the chain move on when given null.
 *
 * This is also the shape a functional harness uses to act as a user: replacing the identity is
 * done by registering an authenticator, so production code needs no seam for tests.
 */
final class FakeAuthenticator implements AuthenticatorInterface
{
    public bool $asked = false;

    public ?Request $seenRequest = null;

    public function __construct(private readonly ?Identity $identity)
    {
    }

    public function authenticate(Request $request): ?Identity
    {
        $this->asked = true;
        $this->seenRequest = $request;

        return $this->identity;
    }
}
