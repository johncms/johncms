<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Config\ConfigRepository;
use Johncms\Http\Request;
use Johncms\Http\SiteBaseUrl;
use PHPUnit\Framework\TestCase;

/**
 * The address absolute URLs are built from.
 *
 * It matters most for the external sign-in services: they compare the callback we register
 * against the one we send, and an http:// address on an HTTPS site is refused — VK answers a
 * bare "Security Error", which says nothing about what to fix.
 */
final class SiteBaseUrlTest extends TestCase
{
    public function testTheSiteSettingIsUsedWhenItIsFilledIn(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => 'https://example.com']]);

        self::assertSame('https://example.com', (new SiteBaseUrl())->resolve(Request::create('http://internal/')));
    }

    public function testATrailingSlashIsNotCarriedOver(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => 'https://example.com/']]);

        self::assertSame('https://example.com', (new SiteBaseUrl())->resolve());
    }

    public function testTheRequestFillsInForAnEmptySetting(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => '']]);

        self::assertSame(
            'https://example.com',
            (new SiteBaseUrl())->resolve(Request::create('https://example.com/auth/vk'))
        );
    }

    /**
     * A site that moved to HTTPS but whose setting still says http:// would hand providers an
     * address they refuse, so a secure request wins over the setting.
     */
    public function testASecureRequestUpgradesTheSchemeOfTheSetting(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => 'http://example.com']]);

        self::assertSame(
            'https://example.com',
            (new SiteBaseUrl())->resolve(Request::create('https://example.com/auth/vk'))
        );
    }

    /**
     * A forwarded scheme from a proxy nobody trusted decides nothing — that is HttpFoundation's
     * rule, and the fix is to name the proxy in `http.trusted_proxies`, not to read the header
     * behind its back.
     */
    public function testAForwardedSchemeFromAnUntrustedProxyIsIgnored(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => 'http://example.com']]);

        $request = Request::create('http://example.com/auth/vk', server: ['HTTP_X_FORWARDED_PROTO' => 'https']);

        self::assertSame('http://example.com', (new SiteBaseUrl())->resolve($request));
    }

    /**
     * Console runs — the scheduler, a command — have no request and nothing but the setting.
     */
    public function testItWorksWithoutARequest(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => 'https://example.com']]);

        self::assertSame('https://example.com', (new SiteBaseUrl())->resolve());
    }
}
