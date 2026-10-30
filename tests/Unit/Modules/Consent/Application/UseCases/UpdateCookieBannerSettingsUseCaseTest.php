<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Consent\Application\UseCases;

use Johncms\Modules\Consent\Application\DTO\CookieBannerSettingsDTO;
use Johncms\Modules\Consent\Application\UseCases\UpdateCookieBannerSettingsUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSystemConfigRepository;

final class UpdateCookieBannerSettingsUseCaseTest extends TestCase
{
    public function testAskingEveryVisitorAgainRaisesTheVersion(): void
    {
        $config = new FakeSystemConfigRepository(['cookie_banner_version' => 3, 'copyright' => 'JohnCMS']);

        (new UpdateCookieBannerSettingsUseCase($config))->execute(new CookieBannerSettingsDTO(1, ['ru' => 'Мы используем cookie'], reshow: true));

        self::assertSame(4, $config->saved['cookie_banner_version']);
        self::assertSame(['ru' => 'Мы используем cookie'], $config->saved['cookie_banner_text']);
        self::assertSame('JohnCMS', $config->saved['copyright']);
    }

    public function testASaveWithoutItKeepsTheVersion(): void
    {
        $config = new FakeSystemConfigRepository(['cookie_banner_version' => 3]);

        (new UpdateCookieBannerSettingsUseCase($config))->execute(new CookieBannerSettingsDTO(0, []));

        self::assertSame(3, $config->saved['cookie_banner_version']);
        self::assertSame(0, $config->saved['cookie_banner_enabled']);
    }

    public function testASiteWithoutAVersionStartsFromTheFirst(): void
    {
        $config = new FakeSystemConfigRepository();

        (new UpdateCookieBannerSettingsUseCase($config))->execute(new CookieBannerSettingsDTO(1, [], reshow: true));

        self::assertSame(2, $config->saved['cookie_banner_version']);
    }
}
