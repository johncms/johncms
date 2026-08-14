<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authentication\AuthenticatorInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

final class CurrentUserTest extends TestCase
{
    public function testWithoutARequestTheVisitorIsAGuest(): void
    {
        $currentUser = new CurrentUser(new AuthenticatorChain([]), $this->permissionResolver(), new RequestStack());

        self::assertTrue($currentUser->isGuest());
        self::assertSame(0, $currentUser->id());
    }

    public function testTheIdentityComesFromTheChain(): void
    {
        $currentUser = $this->currentUser(new FakeAuthenticator(IdentityFactory::user(id: 42)));

        self::assertSame(42, $currentUser->id());
        self::assertFalse($currentUser->isGuest());
    }

    public function testTheChainIsAskedOnlyOncePerRequest(): void
    {
        $authenticator = new FakeAuthenticator(IdentityFactory::user(id: 42));
        $currentUser = $this->currentUser($authenticator);

        $first = $currentUser->identity();
        $second = $currentUser->identity();

        self::assertSame($first, $second);
    }

    /**
     * A worker runtime serves many requests from one process: the identity of the previous
     * visitor must not answer for the next one.
     */
    public function testResetDropsTheIdentityOfTheServedRequest(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        // Answers with a different user every time, so a cached identity is visible as a
        // repeated id rather than as a missing call.
        $authenticator = new class implements AuthenticatorInterface {
            private int $nextId = 1;

            public function authenticate(Request $request): Identity
            {
                return IdentityFactory::user(id: $this->nextId++);
            }
        };

        $currentUser = new CurrentUser(new AuthenticatorChain([$authenticator]), $this->permissionResolver(), $stack);

        self::assertSame(1, $currentUser->id());

        $currentUser->reset();

        self::assertSame(2, $currentUser->id());
    }

    public function testNothingIsResolvedUntilAsked(): void
    {
        $authenticator = new FakeAuthenticator(IdentityFactory::user());
        $this->currentUser($authenticator);

        self::assertFalse($authenticator->asked);
    }

    private function currentUser(FakeAuthenticator $authenticator): CurrentUser
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        return new CurrentUser(new AuthenticatorChain([$authenticator]), $this->permissionResolver(), $stack);
    }
    private function permissionResolver(): PermissionResolver
    {
        return new PermissionResolver(new FakeRoleRepository());
    }
}
