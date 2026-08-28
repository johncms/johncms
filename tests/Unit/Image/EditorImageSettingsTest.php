<?php

declare(strict_types=1);

namespace Tests\Unit\Image;

use Johncms\Config\ConfigRepository;
use Johncms\Image\EditorImageFormat;
use Johncms\Image\EditorImageSettings;
use PHPUnit\Framework\TestCase;

/**
 * What the editor uploads are limited to, as the settings answer it.
 *
 * The values reach a live site from system.local.php, where an administrator may leave anything:
 * a site that has never opened the settings page has no section at all, and a hand-edited file
 * can hold a string where a number belongs.
 */
final class EditorImageSettingsTest extends TestCase
{
    protected function tearDown(): void
    {
        ConfigRepository::init([]);
    }

    public function testASiteThatNeverSavedTheSettingsGetsTheDefaults(): void
    {
        ConfigRepository::init(['johncms' => []]);

        $settings = new EditorImageSettings();

        self::assertSame(EditorImageSettings::DEFAULT_MAX_SIZE_KB, $settings->maxSizeKb());
        self::assertSame(EditorImageSettings::DEFAULT_MAX_WIDTH, $settings->maxWidth());
        self::assertSame(EditorImageSettings::DEFAULT_MAX_HEIGHT, $settings->maxHeight());
        self::assertSame(EditorImageSettings::DEFAULT_QUALITY, $settings->quality());
        self::assertSame(EditorImageFormat::Original, $settings->format());
    }

    public function testTheSizeLimitIsAnsweredInBytesAsWell(): void
    {
        $this->configure(['max_size' => 2048]);

        self::assertSame(2048, (new EditorImageSettings())->maxSizeKb());
        self::assertSame(2048 * 1024, (new EditorImageSettings())->maxSizeBytes());
    }

    public function testABoundOfZeroLeavesTheSideUnconstrained(): void
    {
        $this->configure(['max_width' => 0, 'max_height' => 900]);

        $settings = new EditorImageSettings();

        self::assertNull($settings->maxWidth());
        self::assertSame(900, $settings->maxHeight());
    }

    public function testAQualityOutsideThePercentScaleIsBroughtBackIntoIt(): void
    {
        $this->configure(['quality' => 300]);
        self::assertSame(100, (new EditorImageSettings())->quality());

        $this->configure(['quality' => -5]);
        self::assertSame(1, (new EditorImageSettings())->quality());
    }

    public function testAValueThatIsNotANumberFallsBackToTheDefault(): void
    {
        $this->configure(['max_size' => 'unlimited', 'max_width' => null]);

        $settings = new EditorImageSettings();

        self::assertSame(EditorImageSettings::DEFAULT_MAX_SIZE_KB, $settings->maxSizeKb());
        self::assertSame(EditorImageSettings::DEFAULT_MAX_WIDTH, $settings->maxWidth());
    }

    public function testAFormatTheSiteDoesNotKnowIsReadAsTheOriginalOne(): void
    {
        $this->configure(['convert' => 'webp']);
        self::assertSame(EditorImageFormat::Webp, (new EditorImageSettings())->format());

        $this->configure(['convert' => 'avif']);
        self::assertSame(EditorImageFormat::Original, (new EditorImageSettings())->format());
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function configure(array $settings): void
    {
        ConfigRepository::init(['johncms' => ['editor_images' => $settings]]);
    }
}
