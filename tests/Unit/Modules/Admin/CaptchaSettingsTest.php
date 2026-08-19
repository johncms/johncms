<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Captcha\CaptchaProviderRegistry;
use Johncms\Captcha\Providers\ImageCaptchaProvider;
use Johncms\Captcha\Providers\SmartCaptchaProvider;
use Johncms\Config\ConfigRepository;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\DTO\CaptchaSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\GetCaptchaSettingsUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateCaptchaSettingsUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\RecordingCaptchaConfigRepository;

/**
 * The settings page of the captcha: what it lists and what it writes.
 *
 * Nothing here names a provider the page has to know about — the point of the screen is that a
 * captcha a module registered appears on it by itself.
 */
final class CaptchaSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
        ConfigRepository::init([
            'captcha' => [
                'default'   => 'image',
                'providers' => [
                    'image'        => ['options' => ['width' => 250]],
                    'smartcaptcha' => ['options' => ['site_key' => 'client', 'secret_key' => 'server']],
                ],
            ],
        ]);
    }

    public function testEveryRegisteredProviderIsListedWithItsOwnFields(): void
    {
        $rows = (new GetCaptchaSettingsUseCase($this->registry()))->execute();

        self::assertSame(['image', 'smartcaptcha'], array_column($rows, 'key'));

        $image = $rows[0];
        self::assertTrue($image->isActive);
        self::assertSame(
            ['width', 'height', 'format', 'length_min', 'length_max'],
            array_column($image->fields, 'key')
        );
        // The stored value wins over the default the provider declares.
        self::assertSame('250', $image->fields[0]->value);
        self::assertSame('90', $image->fields[1]->value);
    }

    /**
     * The secret is never sent back to the browser: an input holding it would put it into every
     * page cache and every screenshot of this screen.
     */
    public function testAStoredSecretIsReportedButNotShown(): void
    {
        $rows = (new GetCaptchaSettingsUseCase($this->registry()))->execute();
        $secret = $rows[1]->fields[1];

        self::assertSame('password', $secret->type);
        self::assertSame('', $secret->value);
        self::assertTrue($secret->hasValue);
    }

    public function testSavingWritesTheChosenProviderAndItsSettings(): void
    {
        $config = $this->config();

        (new UpdateCaptchaSettingsUseCase($this->registry(), $config))->execute(
            new CaptchaSettingsDTO(
                default: 'smartcaptcha',
                providers: ['smartcaptcha' => ['site_key' => 'new-client', 'secret_key' => 'new-server']],
            )
        );

        self::assertSame('smartcaptcha', $config->saved['default']);
        self::assertSame(
            ['site_key' => 'new-client', 'secret_key' => 'new-server'],
            $config->saved['providers']['smartcaptcha']['options']
        );
    }

    /**
     * An empty secret field is the normal state of a saved provider, not a request to clear it.
     */
    public function testAnEmptySecretKeepsTheStoredOne(): void
    {
        $config = $this->config();

        (new UpdateCaptchaSettingsUseCase($this->registry(), $config))->execute(
            new CaptchaSettingsDTO(
                default: 'smartcaptcha',
                providers: ['smartcaptcha' => ['site_key' => 'client', 'secret_key' => '']],
            )
        );

        self::assertSame('server', $config->saved['providers']['smartcaptcha']['options']['secret_key']);
    }

    /**
     * The form comes from a browser, so the shape of the configuration is not taken from it.
     */
    public function testUnknownProvidersAndFieldsAreIgnored(): void
    {
        $config = $this->config();

        (new UpdateCaptchaSettingsUseCase($this->registry(), $config))->execute(
            new CaptchaSettingsDTO(
                default: 'nothing-registers-this',
                providers: [
                    'nothing-registers-this' => ['site_key' => 'x'],
                    'image'                  => ['width' => '300', 'root' => '/etc'],
                ],
            )
        );

        // The choice falls back to what was configured rather than to a provider that answers to
        // nothing: the site would be left unable to build a captcha at all.
        self::assertSame('image', $config->saved['default']);
        self::assertArrayNotHasKey('nothing-registers-this', $config->saved['providers']);
        self::assertArrayNotHasKey('root', $config->saved['providers']['image']['options']);
        // Numbers are stored as numbers: everything reading these settings takes them as they are.
        self::assertSame(300, $config->saved['providers']['image']['options']['width']);
    }

    private function registry(): CaptchaProviderRegistry
    {
        return new CaptchaProviderRegistry(
            [
                new ImageCaptchaProvider(new Session(new MockArraySessionStorage()), new NullLogger()),
                new SmartCaptchaProvider(new MockHttpClient([])),
            ]
        );
    }

    private function config(): RecordingCaptchaConfigRepository
    {
        return new RecordingCaptchaConfigRepository();
    }
}
