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
     * Background that leaves the padding of savePadded() see-through.
     *
     * Only formats with an alpha channel can keep it: written to a JPEG it comes out as the
     * background color of the driver, because the format has nowhere to put transparency.
     */
    public const string TRANSPARENT = 'transparent';

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
     * Write a copy of exactly the given size, cropping away what does not fit.
     *
     * The picture fills the frame completely and keeps its proportions; the part of it that
     * sticks out is cut off. What a fixed tile needs — a cover, a card of a catalogue, a
     * square avatar — when there must be no empty margin around the picture.
     *
     * @param ImagePosition $position The part of the picture the crop keeps.
     * @throws ImageProcessingException
     */
    public function saveCropped(
        string $source,
        string $target,
        int $width,
        int $height,
        ImagePosition $position = ImagePosition::Center,
        int $quality = self::DEFAULT_QUALITY,
    ): void;

    /**
     * Write a copy of exactly the given size with the whole picture inside it, filling what is
     * left over with the background.
     *
     * The other way of arriving at a fixed size: nothing is cut off, so the picture arrives
     * whole and the frame gains margins on the two sides where it does not fit. For a
     * catalogue where cropping the goods is not an option.
     *
     * @param string $background Any color the driver understands ("fff", "#ffcc00",
     *                           "rgb(255, 0, 0)"), or self::TRANSPARENT.
     * @throws ImageProcessingException
     */
    public function savePadded(
        string $source,
        string $target,
        int $width,
        int $height,
        string $background = 'ffffff',
        int $quality = self::DEFAULT_QUALITY,
    ): void;

    /**
     * Write a copy of exactly the given size, **without keeping the proportions**.
     *
     * The picture is pulled to the frame, so anything of a different shape comes out distorted
     * — which is why the name says so at the call site. Reach for saveCropped() or
     * savePadded() unless the source is already of the proportions asked for.
     *
     * @throws ImageProcessingException
     */
    public function saveStretched(
        string $source,
        string $target,
        int $width,
        int $height,
        int $quality = self::DEFAULT_QUALITY,
    ): void;

    /**
     * Write a copy of the source with a watermark laid over it.
     *
     * The watermark is used at its own size — scale the file itself, or prepare one per size
     * of picture; enlarging a small mark to fit a big photo only makes it blurry.
     *
     * @param string $watermark Path of the picture to lay over the source.
     * @param int $opacity 0 (invisible) to 100 (opaque).
     * @param int $offset Distance from the edge, in pixels, so the mark does not touch it.
     * @throws ImageProcessingException
     */
    public function saveWatermarked(
        string $source,
        string $target,
        string $watermark,
        ImagePosition $position = ImagePosition::BottomRight,
        int $opacity = 100,
        int $offset = 0,
        int $quality = self::DEFAULT_QUALITY,
    ): void;

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
