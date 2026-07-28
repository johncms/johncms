<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use Johncms\Http\Request;
use Johncms\System\Users\User as LegacyUser;
use Johncms\System\Users\UserFactory as LegacyUserFactory;
use Johncms\Users\CurrentUserAuthenticator;
use Johncms\Users\User;
use Johncms\Users\UserFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

final class CurrentUserAuthenticatorTest extends TestCase
{
    public function testTheVisitorIsLoadedIntoBothSharedInstances(): void
    {
        $stack = new RequestStack();
        $stack->push($request = Request::create('/'));

        $legacyUser = new LegacyUser();
        $user = new User();

        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::once())->method('authenticate')->with($legacyUser, $request);

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::once())->method('authenticate')->with($user, $request);

        (new CurrentUserAuthenticator($stack, $legacyFactory, $legacyUser, $factory, $user))->authenticate();
    }

    public function testTheSameRequestIsAuthenticatedOnlyOnce(): void
    {
        // Under FPM the boot and the kernel serve the same request object; authenticating twice
        // would mean a second round of database queries on every page.
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::once())->method('authenticate');

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::once())->method('authenticate');

        $authenticator = new CurrentUserAuthenticator($stack, $legacyFactory, new LegacyUser(), $factory, new User());
        $authenticator->authenticate();
        $authenticator->authenticate();
    }

    public function testTheNextRequestIsAuthenticatedAgain(): void
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        $legacyFactory = $this->createMock(LegacyUserFactory::class);
        $legacyFactory->expects(self::exactly(2))->method('authenticate');

        $factory = $this->createMock(UserFactory::class);
        $factory->expects(self::exactly(2))->method('authenticate');

        $authenticator = new CurrentUserAuthenticator($stack, $legacyFactory, new LegacyUser(), $factory, new User());
        $authenticator->authenticate();

        $stack->pop();
        $stack->push(Request::create('/forum'));
        $authenticator->authenticate();
    }

    public function testAuthenticatingWithoutARequestFails(): void
    {
        $authenticator = new CurrentUserAuthenticator(
            new RequestStack(),
            $this->createMock(LegacyUserFactory::class),
            new LegacyUser(),
            $this->createMock(UserFactory::class),
            new User()
        );

        $this->expectException(RuntimeException::class);
        $authenticator->authenticate();
    }
}
