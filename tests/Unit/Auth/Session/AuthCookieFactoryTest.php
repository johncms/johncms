<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Session;

use Johncms\Auth\Impersonation\ImpersonationSettings;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\SessionSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class AuthCookieFactoryTest extends TestCase
{
    /**
     * The flags the previous cookies lacked, which is why they were readable by any script and
     * travelled over plain HTTP.
     */
    public function testTheCookieIsAlwaysProtected(): void
    {
        $cookie = $this->factory()->create('secret', true, time() + 60, secure: true);

        self::assertTrue($cookie->isHttpOnly());
        self::assertTrue($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('jc_auth', $cookie->getName());
        self::assertSame('secret', $cookie->getValue());
    }

    /**
     * Forcing Secure on a site served over plain HTTP would make signing in quietly impossible.
     */
    public function testSecureFollowsTheScheme(): void
    {
        self::assertFalse($this->factory()->create('secret', true, time() + 60, secure: false)->isSecure());
    }

    public function testRememberedSessionsGetAPersistentCookie(): void
    {
        $expiresAt = time() + 3600;

        $cookie = $this->factory()->create('secret', true, $expiresAt, secure: false);

        self::assertSame($expiresAt, $cookie->getExpiresTime());
    }

    /**
     * Without "remember me" the browser drops the cookie itself; the row expiry is what really
     * limits the visit, since browsers restore session cookies when reopening tabs.
     */
    public function testWithoutRememberTheCookieIsASessionOne(): void
    {
        $cookie = $this->factory()->create('secret', false, time() + 3600, secure: false);

        self::assertSame(0, $cookie->getExpiresTime());
    }

    public function testForgettingSendsAnExpiredCookieWithTheSameAttributes(): void
    {
        $cookie = $this->factory()->forget(secure: true);

        self::assertSame('jc_auth', $cookie->getName());
        self::assertSame('', $cookie->getValue());
        self::assertLessThan(time(), $cookie->getExpiresTime());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('/', $cookie->getPath());
    }

    public function testTheCookieNameFollowsTheSettings(): void
    {
        $factory = new AuthCookieFactory(new SessionSettings(cookieName: 'custom_auth'), new ImpersonationSettings());

        self::assertSame('custom_auth', $factory->create('secret', true, null, secure: false)->getName());
    }

    private function factory(): AuthCookieFactory
    {
        return new AuthCookieFactory(new SessionSettings(), new ImpersonationSettings());
    }
}
