<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authentication\AuthenticatorInterface;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAuthenticator;
use Tests\Support\IdentityFactory;

final class AuthenticatorChainTest extends TestCase
{
    public function testAnUnrecognisedRequestBelongsToAGuest(): void
    {
        $chain = new AuthenticatorChain([new FakeAuthenticator(null)]);

        self::assertTrue($chain->authenticate(Request::create('/'))->isGuest());
    }

    public function testEmptyChainAnswersGuest(): void
    {
        self::assertTrue((new AuthenticatorChain([]))->authenticate(Request::create('/'))->isGuest());
    }

    public function testFirstMatchWins(): void
    {
        $first = new FakeAuthenticator(IdentityFactory::user(id: 1));
        $second = new FakeAuthenticator(IdentityFactory::user(id: 2));

        $identity = (new AuthenticatorChain([$first, $second]))->authenticate(Request::create('/'));

        self::assertSame(1, $identity->userId);
        self::assertFalse($second->asked);
    }

    public function testLaterAuthenticatorsAnswerWhenEarlierOnesDoNot(): void
    {
        $identity = (new AuthenticatorChain([
            new FakeAuthenticator(null),
            new FakeAuthenticator(IdentityFactory::user(id: 7)),
        ]))->authenticate(Request::create('/'));

        self::assertSame(7, $identity->userId);
    }

    public function testTheRequestReachesTheAuthenticator(): void
    {
        $authenticator = new FakeAuthenticator(null);
        $request = Request::create('/forum');

        (new AuthenticatorChain([$authenticator]))->authenticate($request);

        self::assertSame($request, $authenticator->seenRequest);
    }

    /**
     * Guards the shape the whole layer relies on: an authenticator returns null for "not mine",
     * never a guest identity — a guest returned here would stop the chain and hide the token
     * authenticator behind it.
     */
    public function testAuthenticatorContractIsNullForUnknown(): void
    {
        $authenticator = new class implements AuthenticatorInterface {
            public function authenticate(Request $request): ?Identity
            {
                return null;
            }
        };

        self::assertNull($authenticator->authenticate(Request::create('/')));
    }
}
