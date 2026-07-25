<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Config\ConfigRepository;
use Johncms\Http\Request;
use Johncms\Http\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * Tests for the classic-FPM request factory (plan stages 1b and 1e).
 *
 * The factory mutates HttpFoundation global static state (the request factory, trusted proxies
 * and trusted hosts), so every test resets that state in tearDown to avoid leaking into others.
 */
final class RequestFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setFactory(null);
        Request::setTrustedProxies([], 0);
        Request::setTrustedHosts([]);
        ConfigRepository::init([]);

        parent::tearDown();
    }

    public function testShippedConfigTrustsNoProxyAndNoForwardedHeader(): void
    {
        // A non-empty default here is a security decision, not a convenience one: the bundled
        // nginx passes the visitor's own X-Forwarded-For through untouched, and under Docker
        // port publishing REMOTE_ADDR is the bridge gateway for every visitor — so trusting
        // private ranges would let any client choose their own address and evade IP bans.
        // array_replace_recursive() in ConfigLoader also means a non-empty default could not be
        // switched off from a *.local.php override.
        $config = require ROOT_PATH . 'config/autoload/http.global.php';

        self::assertSame([], $config['http']['trusted_proxies']);
        self::assertSame([], $config['http']['trusted_hosts']);
    }

    public function testCreatedRequestIsTheJohncmsSubclass(): void
    {
        $request = (new RequestFactory())($this->createStub(ContainerInterface::class));

        self::assertInstanceOf(Request::class, $request);
    }

    public function testConfigureMakesCreateFromGlobalsReturnTheSubclass(): void
    {
        RequestFactory::configure();

        self::assertInstanceOf(Request::class, Request::createFromGlobals());
    }

    public function testTrustedProxiesAreNotSetWhenConfigIsEmpty(): void
    {
        RequestFactory::configure();

        self::assertSame([], Request::getTrustedProxies());
    }

    public function testMalformedTrustedProxiesConfigIsIgnoredInsteadOfCrashing(): void
    {
        // A common operator typo: a bare string instead of a list. It must not reach
        // setTrustedProxies() (which would throw a TypeError at boot).
        ConfigRepository::init([
            'http' => [
                'trusted_proxies' => '10.0.0.0/8',
            ],
        ]);

        RequestFactory::configure();

        self::assertSame([], Request::getTrustedProxies());
    }

    public function testTrustedProxiesAreAppliedFromConfig(): void
    {
        ConfigRepository::init([
            'http' => [
                'trusted_proxies' => ['192.168.1.1', '10.0.0.0/8'],
                'trusted_headers' => null,
            ],
        ]);

        RequestFactory::configure();

        self::assertSame(['192.168.1.1', '10.0.0.0/8'], Request::getTrustedProxies());
    }

    public function testTrustedHostsAreNotSetWhenConfigIsEmpty(): void
    {
        RequestFactory::configure();

        self::assertSame([], Request::getTrustedHosts());
    }

    public function testMalformedTrustedHostsConfigIsIgnoredInsteadOfCrashing(): void
    {
        ConfigRepository::init([
            'http' => [
                'trusted_hosts' => '^example\.com$',
            ],
        ]);

        RequestFactory::configure();

        self::assertSame([], Request::getTrustedHosts());
    }

    public function testTrustedHostsAreAppliedFromConfig(): void
    {
        ConfigRepository::init([
            'http' => [
                'trusted_hosts' => ['^example\.com$'],
            ],
        ]);

        RequestFactory::configure();

        self::assertSame(['{^example\.com$}i'], Request::getTrustedHosts());
    }

    public function testRequestWithAnUntrustedHostIsRejected(): void
    {
        ConfigRepository::init([
            'http' => [
                'trusted_hosts' => ['^example\.com$'],
            ],
        ]);
        RequestFactory::configure();

        $request = Request::create('http://evil.test/');

        $this->expectException(SuspiciousOperationException::class);

        $request->getHost();
    }

    public function testRequestWithATrustedHostPasses(): void
    {
        ConfigRepository::init([
            'http' => [
                'trusted_hosts' => ['^example\.com$'],
            ],
        ]);
        RequestFactory::configure();

        $request = Request::create('http://example.com/');

        self::assertSame('example.com', $request->getHost());
    }
}
