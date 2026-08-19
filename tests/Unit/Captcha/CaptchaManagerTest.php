<?php

declare(strict_types=1);

namespace Tests\Unit\Captcha;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Captcha\CaptchaException;
use Johncms\Captcha\CaptchaManager;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Captcha\Providers\ImageCaptchaProvider;
use Johncms\Captcha\Providers\SmartCaptchaProvider;
use Johncms\Config\ConfigRepository;
use Johncms\Http\Session;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Which provider the forms actually get.
 */
final class CaptchaManagerTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testTheConfiguredProviderIsUsed(): void
    {
        ConfigRepository::init([
            'captcha' => [
                'default'   => 'smartcaptcha',
                'providers' => [
                    'smartcaptcha' => ['options' => ['site_key' => 'client', 'secret_key' => 'server']],
                ],
            ],
        ]);

        self::assertSame('smartcaptcha', $this->manager()->provider()->key());
        self::assertSame('smart-token', $this->manager()->fieldName());
    }

    /**
     * A service whose keys were never filled in must not become a way past the check: the site
     * falls back to the picture, which needs nothing to work.
     */
    public function testAnUnconfiguredProviderFallsBackToThePicture(): void
    {
        ConfigRepository::init(['captcha' => ['default' => 'smartcaptcha']]);

        self::assertSame(ImageCaptchaProvider::KEY, $this->manager()->provider()->key());
    }

    /**
     * The same for a provider that is named in the configuration but registered by nothing —
     * a module that was removed, or a typo.
     */
    public function testAnUnknownProviderFallsBackToThePicture(): void
    {
        ConfigRepository::init(['captcha' => ['default' => 'nothing-registers-this']]);

        self::assertSame(ImageCaptchaProvider::KEY, $this->manager()->provider()->key());
    }

    /**
     * With the built-in provider gone as well there is nothing left to fall back to, and a
     * captcha that silently checks nothing is worse than a page that fails.
     */
    public function testWithoutAnyProviderTheManagerRefusesToGuess(): void
    {
        ConfigRepository::init(['captcha' => ['default' => 'smartcaptcha']]);
        $manager = new CaptchaManager(new CaptchaProviderRegistry([]), new NullLogger());

        $this->expectException(CaptchaException::class);
        $manager->provider();
    }

    private function manager(): CaptchaManager
    {
        return new CaptchaManager(
            new CaptchaProviderRegistry(
                [
                    new ImageCaptchaProvider(new Session(new MockArraySessionStorage()), new NullLogger()),
                    new SmartCaptchaProvider(new MockHttpClient([])),
                ]
            ),
            new NullLogger(),
        );
    }
}
