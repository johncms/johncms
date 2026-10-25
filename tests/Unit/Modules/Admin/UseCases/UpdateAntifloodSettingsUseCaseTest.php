<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\DTO\AntifloodSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateAntifloodSettingsUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSystemConfigRepository;

/**
 * What the antiflood settings are allowed to write into the configuration.
 *
 * The form is a mode and four numbers typed by an administrator, and the values decide how long
 * every visitor waits between two posts: a delay of 0 switches the antiflood off, one of a day
 * stops the site talking.
 */
final class UpdateAntifloodSettingsUseCaseTest extends TestCase
{
    public function testTheSettingsAreWrittenUnderTheirOwnSection(): void
    {
        $configRepository = new FakeSystemConfigRepository(['copyright' => 'JohnCMS']);

        (new UpdateAntifloodSettingsUseCase($configRepository))->execute(
            new AntifloodSettingsDTO(mode: 2, day: 15, night: 120, dayFrom: 8, dayTo: 21)
        );

        self::assertSame(
            ['mode' => 2, 'day' => 15, 'night' => 120, 'dayfrom' => 8, 'dayto' => 21],
            $configRepository->saved['antiflood'] ?? null
        );
        // The rest of the configuration is carried over rather than overwritten.
        self::assertSame('JohnCMS', $configRepository->saved['copyright'] ?? null);
    }

    public function testDelaysOutsideTheAllowedRangeAreBroughtBackIntoIt(): void
    {
        $saved = $this->save(new AntifloodSettingsDTO(mode: 3, day: 0, night: 86400, dayFrom: 10, dayTo: 22));

        self::assertSame(4, $saved['day']);
        self::assertSame(300, $saved['night']);
    }

    public function testHoursOutsideTheirPartOfTheDayAreBroughtBackIntoIt(): void
    {
        $early = $this->save(new AntifloodSettingsDTO(mode: 2, day: 10, night: 30, dayFrom: 0, dayTo: 12));
        $late = $this->save(new AntifloodSettingsDTO(mode: 2, day: 10, night: 30, dayFrom: 20, dayTo: 30));

        self::assertSame([6, 17], [$early['dayfrom'], $early['dayto']]);
        self::assertSame([12, 23], [$late['dayfrom'], $late['dayto']]);
    }

    public function testAnUnknownModeFallsBackToTheAdaptiveOne(): void
    {
        foreach ([0, 5, -1] as $mode) {
            $saved = $this->save(new AntifloodSettingsDTO(mode: $mode, day: 10, night: 30, dayFrom: 10, dayTo: 22));

            self::assertSame(1, $saved['mode'], 'mode ' . $mode);
        }
    }

    /**
     * @return array<string, int> The antiflood section as it was written.
     */
    private function save(AntifloodSettingsDTO $dto): array
    {
        $configRepository = new FakeSystemConfigRepository();
        (new UpdateAntifloodSettingsUseCase($configRepository))->execute($dto);

        return $configRepository->saved['antiflood'];
    }
}
