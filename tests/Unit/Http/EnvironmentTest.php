<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\Environment;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for visitor-address resolution.
 */
final class EnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Trusted proxies are global static state on Request and other test classes mutate it,
        // so each test establishes its own starting point instead of inheriting one.
        Request::setTrustedProxies([], 0);
    }

    protected function tearDown(): void
    {
        Request::setTrustedProxies([], 0);

        parent::tearDown();
    }

    public function testReturnsTheRemoteAddressWhenNothingIsProxied(): void
    {
        $env = $this->environmentFor(['REMOTE_ADDR' => '203.0.113.7']);

        self::assertSame('203.0.113.7', $env->getIp(false));
        self::assertSame(ip2long('203.0.113.7'), $env->getIp());
        self::assertSame(0, $env->getIpViaProxy());
    }

    public function testForwardedHeaderFromAnUntrustedSourceIsIgnored(): void
    {
        // The security fix: previously this header was parsed by hand and recorded as
        // ip_via_proxy, so any client could put an arbitrary address into the logs.
        $env = $this->environmentFor([
            'REMOTE_ADDR'          => '203.0.113.7',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.9',
        ]);

        self::assertSame('203.0.113.7', $env->getIp(false));
        self::assertSame(0, $env->getIpViaProxy());
    }

    public function testForwardedHeaderIsIgnoredEvenWhenThePeerAddressIsPrivate(): void
    {
        // The exploit that keeps 'trusted_proxies' empty by default: the bundled nginx speaks
        // FastCGI and forwards the visitor's own X-Forwarded-For untouched, while REMOTE_ADDR is
        // whoever connected to nginx. Under Docker port publishing that peer is the bridge
        // gateway for every visitor, so trusting private ranges would hand any client on the
        // internet a self-chosen address — and with it, ban evasion.
        $env = $this->environmentFor([
            'REMOTE_ADDR'          => '172.21.0.1',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.9',
        ]);

        self::assertSame('172.21.0.1', $env->getIp(false));
        self::assertSame(0, $env->getIpViaProxy());
    }

    public function testBehindATrustedProxyTheVisitorAddressIsUsed(): void
    {
        // The bug fix, once an operator declares their proxy: with a reverse proxy in front of
        // the application every visitor used to be recorded under the proxy address.
        Request::setTrustedProxies(['172.18.0.3'], Request::HEADER_X_FORWARDED_FOR);

        $env = $this->environmentFor([
            'REMOTE_ADDR'          => '172.18.0.3',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        ]);

        self::assertSame('203.0.113.7', $env->getIp(false));
        self::assertSame(ip2long('203.0.113.7'), $env->getIp());
        self::assertSame(0, $env->getIpViaProxy());
    }

    public function testVisitorBehindTheirOwnProxyKeepsBothAddresses(): void
    {
        Request::setTrustedProxies(['172.18.0.3'], Request::HEADER_X_FORWARDED_FOR);

        $env = $this->environmentFor([
            'REMOTE_ADDR'          => '172.18.0.3',
            // Left to right: the claimed originator, then the proxy that reached our own proxy.
            'HTTP_X_FORWARDED_FOR' => '198.51.100.9, 203.0.113.7',
        ]);

        self::assertSame('203.0.113.7', $env->getIp(false), 'nearest untrusted hop');
        self::assertSame('198.51.100.9', $env->getIpViaProxy(false), 'what that hop claims');
    }

    public function testShortFormCallDoesNotPoisonTheLongFormResult(): void
    {
        // Regression: the previous implementation cached whatever the first call produced, so
        // Users\UserFactory::ipHistory() compared a dotted string against an integer column.
        Request::setTrustedProxies(['172.18.0.3'], Request::HEADER_X_FORWARDED_FOR);

        $env = $this->environmentFor([
            'REMOTE_ADDR'          => '172.18.0.3',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.9, 203.0.113.7',
        ]);

        self::assertSame('198.51.100.9', $env->getIpViaProxy(false));
        self::assertSame(ip2long('198.51.100.9'), $env->getIpViaProxy());
    }

    public function testMissingRemoteAddressFallsBackToLoopback(): void
    {
        $env = $this->environmentFor([]);

        self::assertSame('127.0.0.1', $env->getIp(false));
        self::assertSame(0, $env->getIpViaProxy());
    }

    public function testIpv6VisitorDegradesToLoopbackInBothRepresentations(): void
    {
        // The `ip` columns are unsigned integers, so IPv6 cannot be stored and degrades to the
        // loopback address. What matters here is that both representations agree: the long form
        // used to return 0 for a visitor the short form reported as 127.0.0.1, so callers that
        // mix them (Users\UserFactory::ipHistory()) could not match their own records.
        $env = $this->environmentFor(['REMOTE_ADDR' => '2001:db8::1']);

        self::assertSame('127.0.0.1', $env->getIp(false));
        self::assertSame(ip2long('127.0.0.1'), $env->getIp());
    }

    /**
     * @param array<string, string> $server
     */
    private function environmentFor(array $server): Environment
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/', 'GET', [], [], [], $server));

        return new Environment($requestStack);
    }
}
