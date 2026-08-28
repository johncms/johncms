<?php

declare(strict_types=1);

namespace Tests\Unit\Image;

use Johncms\Image\EditorImageFormat;
use PHPUnit\Framework\TestCase;

/**
 * The extension a stored picture gets, which is also the format the processor encodes into.
 */
final class EditorImageFormatTest extends TestCase
{
    public function testTheOriginalFormatKeepsTheOneTheUploadCameIn(): void
    {
        $format = EditorImageFormat::Original;

        self::assertSame('png', $format->extension(IMAGETYPE_PNG));
        self::assertSame('gif', $format->extension(IMAGETYPE_GIF));
        self::assertSame('webp', $format->extension(IMAGETYPE_WEBP));
        self::assertSame('jpg', $format->extension(IMAGETYPE_JPEG));
    }

    public function testTheChosenFormatIsTheSameWhateverWasUploaded(): void
    {
        self::assertSame('webp', EditorImageFormat::Webp->extension(IMAGETYPE_PNG));
        self::assertSame('jpg', EditorImageFormat::Jpeg->extension(IMAGETYPE_PNG));
    }
}
