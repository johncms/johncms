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
 * Turns an uploaded picture into the sizes the site stores.
 *
 * The methods are named after what the site needs, not after the operations of the library
 * behind them: which library resizes and blurs is deliberately invisible here, the same way
 * it is for HtmlSanitizerInterface. That is what keeps a major upgrade of the library — or a
 * different library altogether — inside a single implementation instead of every use case.
 *
 * Both methods take and produce file paths, because that is what every caller has: an upload
 * lands on disk (see UploadedFileDTO::$tmpPath) and the result belongs on disk too. The
 * output format is taken from the extension of the target path.
 */
interface ImageProcessorInterface
{
    /**
     * Encoding quality of the formats that have one, in percent.
     *
     * The value the CMS has always written; lowering it is a decision about every stored
     * picture, not about a single call site.
     */
    public const int DEFAULT_QUALITY = 100;

    /**
     * Write a copy of the source scaled down to fit within the given bounds.
     *
     * The aspect ratio is kept and a picture already smaller than the bounds is copied as it
     * is — the CMS never enlarges what a visitor uploaded. A null side is unconstrained, so a
     * width alone scales by width.
     *
     * @throws ImageProcessingException
     */
    public function saveScaledDown(
        string $source,
        string $target,
        ?int $width = null,
        ?int $height = null,
        int $quality = self::DEFAULT_QUALITY,
    ): void;

    /**
     * Write a copy of the source at its original size, in the format of the target extension.
     *
     * What a caller reaches for when it stores an upload under a fixed extension: the picture
     * has to be re-encoded, or a JPEG ends up saved under the name of a PNG.
     *
     * @throws ImageProcessingException
     */
    public function saveConverted(string $source, string $target, int $quality = self::DEFAULT_QUALITY): void;

    /**
     * Write a thumbnail of exactly the given size: the source cropped to fill it and blurred
     * as a backdrop, with the scaled-down source centered on top.
     *
     * The backdrop is what lets pictures of any proportion sit in a grid of equal tiles
     * without letterboxing them.
     *
     * @throws ImageProcessingException
     */
    public function saveBlurredThumbnail(
        string $source,
        string $target,
        int $width,
        int $height,
        int $quality = self::DEFAULT_QUALITY,
    ): void;
}
