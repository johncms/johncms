<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\System\Users\User as LegacyUser;
use Johncms\System\Users\UserFactory as LegacyUserFactory;
use Johncms\Users\CurrentUserAuthenticator;
use Johncms\Users\User;
use Johncms\Users\UserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

final class CurrentUserAuthenticatorTest extends TestCase
{
    public function testTheVisitorIsLoadedIntoBothSharedInstances(): void
    {
        $legacyUser = new LegacyUser();
        $user = new User();

        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::once())->method('load')->with($legacyUser, 42);

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::once())->method('load')->with($user, 42);

        $authenticator = new CurrentUserAuthenticator(
            $this->currentUser(IdentityFactory::user(id: 42)),
            $legacyFactory,
            $legacyUser,
            $factory,
            $user
        );

        $authenticator->authenticate();
    }

    /**
     * Nobody is signed in: the models are still filled, with nothing, so whatever the previous
     * visitor left in them cannot answer for this request.
     */
    public function testAGuestIsAlsoLoaded(): void
    {
        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::once())->method('load')->with(self::anything(), 0);

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::once())->method('load')->with(self::anything(), 0);

        $authenticator = new CurrentUserAuthenticator(
            new CurrentUser(new AuthenticatorChain([]), $this->permissionResolver(), new RequestStack()),
            $legacyFactory,
            new LegacyUser(),
            $factory,
            new User()
        );

        $authenticator->authenticate();
    }

    public function testTheSameVisitorIsLoadedOnlyOnce(): void
    {
        // The kernel may call this more than once for one request; each call is two queries.
        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::once())->method('load');

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::once())->method('load');

        $authenticator = new CurrentUserAuthenticator(
            $this->currentUser(IdentityFactory::user(id: 42)),
            $legacyFactory,
            new LegacyUser(),
            $factory,
            new User()
        );

        $authenticator->authenticate();
        $authenticator->authenticate();
    }

    /**
     * Signing in and stepping into impersonation change the visitor inside one request, and the
     * models have to follow.
     */
    public function testForgettingForcesAReload(): void
    {
        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::exactly(2))->method('load');

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::exactly(2))->method('load');

        $authenticator = new CurrentUserAuthenticator(
            $this->currentUser(IdentityFactory::user(id: 42)),
            $legacyFactory,
            new LegacyUser(),
            $factory,
            new User()
        );

        $authenticator->authenticate();
        $authenticator->forget();
        $authenticator->authenticate();
    }

    private function currentUser(\Johncms\Auth\Identity $identity): CurrentUser
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        return new CurrentUser(new AuthenticatorChain([new FakeAuthenticator($identity)]), $this->permissionResolver(), $stack);
    }
    private function permissionResolver(): PermissionResolver
    {
        return new PermissionResolver(new FakeRoleRepository());
    }
}
