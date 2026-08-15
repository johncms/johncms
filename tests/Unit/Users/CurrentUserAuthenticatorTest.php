<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use Johncms\Users\CurrentUserAuthenticator;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;

final class CurrentUserAuthenticatorTest extends TestCase
{
    /**
     * Nobody is signed in: the shared model is still refilled, with nothing, so whatever the
     * previous visitor left in it cannot answer for this request.
     */
    public function testTheStateOfThePreviousVisitorIsDropped(): void
    {
        $shared = new User();
        $shared->setRawAttributes(['id' => 42, 'name' => 'Somebody'], true);
        $shared->exists = true;

        $authenticator = new CurrentUserAuthenticator($this->currentUser(Identity::guest()), $shared);
        $authenticator->authenticate();

        self::assertSame([], $shared->getAttributes());
        self::assertFalse($shared->exists);
    }

    /**
     * The mirror runs once per request, and a second call must not undo what the request has
     * done to the model in between.
     */
    public function testTheSameVisitorIsMirroredOnlyOnce(): void
    {
        $shared = new User();

        $authenticator = new CurrentUserAuthenticator($this->currentUser(Identity::guest()), $shared);
        $authenticator->authenticate();

        $shared->setRawAttributes(['name' => 'Changed by the request'], true);
        $authenticator->authenticate();

        self::assertSame(['name' => 'Changed by the request'], $shared->getAttributes());
    }

    private function currentUser(Identity $identity): CurrentUser
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        return new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator($identity)]),
            new PermissionResolver(new FakeRoleRepository()),
            $stack,
            new FakeUserRepository()
        );
    }
}
