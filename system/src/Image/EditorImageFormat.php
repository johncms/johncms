<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Image;

/**
 * The format a picture uploaded into the editor is stored in.
 *
 * `Original` keeps the format the visitor sent, which is what a site that does not want its
 * pictures re-encoded picks; the other two re-encode every upload, and that is where the weight
 * of a photo actually goes away.
 */
enum EditorImageFormat: string
{
    case Original = 'original';
    case Jpeg = 'jpeg';
    case Webp = 'webp';

    public static function fromValue(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::Original) : self::Original;
    }

    /**
     * The extension the picture is stored under, and with it the format the processor encodes:
     * the target path is what the processor reads the format from.
     *
     * @param int $imageType One of the IMAGETYPE_* constants of the source picture.
     */
    public function extension(int $imageType): string
    {
        return match ($this) {
            self::Jpeg => 'jpg',
            self::Webp => 'webp',
            self::Original => self::extensionOfType($imageType),
        };
    }

    /**
     * @param int $imageType One of the IMAGETYPE_* constants.
     */
    private static function extensionOfType(int $imageType): string
    {
        return match ($imageType) {
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default => 'jpg',
        };
    }
}
