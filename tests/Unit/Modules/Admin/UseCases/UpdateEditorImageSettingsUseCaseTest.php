<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\DTO\EditorImageSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateEditorImageSettingsUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSystemConfigRepository;

/**
 * What the settings page is allowed to write into the configuration.
 *
 * The form is a handful of numbers typed by an administrator, and the values reach every upload
 * on the site: a quality of 3000 or a bound of 4 pixels would be obeyed by the processor.
 */
final class UpdateEditorImageSettingsUseCaseTest extends TestCase
{
    public function testTheSettingsAreWrittenUnderTheirOwnSection(): void
    {
        $configRepository = $this->configRepository(['copyright' => 'JohnCMS']);

        (new UpdateEditorImageSettingsUseCase($configRepository))->execute(
            new EditorImageSettingsDTO(maxSize: 2048, maxWidth: 1280, maxHeight: 1024, quality: 80, convert: 'webp')
        );

        self::assertSame(
            [
                'max_size'   => 2048,
                'max_width'  => 1280,
                'max_height' => 1024,
                'quality'    => 80,
                'convert'    => 'webp',
            ],
            $configRepository->saved['editor_images'] ?? null
        );
        // The rest of the configuration is carried over rather than overwritten.
        self::assertSame('JohnCMS', $configRepository->saved['copyright'] ?? null);
    }

    public function testValuesOutsideTheAllowedRangeAreBroughtBackIntoIt(): void
    {
        $configRepository = $this->configRepository();

        (new UpdateEditorImageSettingsUseCase($configRepository))->execute(
            new EditorImageSettingsDTO(maxSize: 0, maxWidth: 4, maxHeight: 999999, quality: 300, convert: 'webp')
        );

        $saved = $configRepository->saved['editor_images'];

        self::assertSame(1, $saved['max_size']);
        self::assertSame(100, $saved['max_width']);
        self::assertSame(10000, $saved['max_height']);
        self::assertSame(100, $saved['quality']);
    }

    public function testABoundOfZeroStaysZeroBecauseThatIsHowASideIsLeftUnconstrained(): void
    {
        $configRepository = $this->configRepository();

        (new UpdateEditorImageSettingsUseCase($configRepository))->execute(
            new EditorImageSettingsDTO(maxSize: 1024, maxWidth: 0, maxHeight: 0, quality: 82, convert: 'original')
        );

        self::assertSame(0, $configRepository->saved['editor_images']['max_width']);
        self::assertSame(0, $configRepository->saved['editor_images']['max_height']);
    }

    public function testAFormatTheSiteDoesNotKnowIsStoredAsTheOriginalOne(): void
    {
        $configRepository = $this->configRepository();

        (new UpdateEditorImageSettingsUseCase($configRepository))->execute(
            new EditorImageSettingsDTO(maxSize: 1024, maxWidth: 800, maxHeight: 800, quality: 82, convert: 'bmp')
        );

        self::assertSame('original', $configRepository->saved['editor_images']['convert']);
    }

    /**
     * @param array<string, mixed> $existing
     */
    private function configRepository(array $existing = []): FakeSystemConfigRepository
    {
        return new FakeSystemConfigRepository($existing);
    }
}
