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

use Intervention\Image\Alignment;
use Intervention\Image\Color;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ExceptionInterface as InterventionException;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * The Intervention Image backed processor.
 *
 * This is the only file in the CMS that knows the library exists. Everything it does with it
 * is here: choosing the driver, the decoding options, and the traps of the API.
 */
final class InterventionImageProcessor implements ImageProcessorInterface
{
    /**
     * How strongly the backdrop of a thumbnail is blurred, on the 0-100 scale of the library.
     * The value the CMS has always used.
     */
    private const int BACKDROP_BLUR = 20;

    /**
     * Built when the first picture is processed: a page that shows no picture never pays for
     * assembling the driver.
     */
    private ?ImageManager $manager = null;

    public function saveScaledDown(
        string $source,
        string $target,
        ?int $width = null,
        ?int $height = null,
        int $quality = self::DEFAULT_QUALITY,
    ): void {
        if ($width === null && $height === null) {
            throw new ImageProcessingException('At least one of $width and $height must be given.');
        }

        try {
            $this->save(
                $this->manager()->decodePath($source)->scaleDown($width, $height),
                $target,
                $quality
            );
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    public function saveConverted(string $source, string $target, int $quality = self::DEFAULT_QUALITY): void
    {
        try {
            $this->save($this->manager()->decodePath($source), $target, $quality);
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    public function saveCropped(
        string $source,
        string $target,
        int $width,
        int $height,
        ImagePosition $position = ImagePosition::Center,
        int $quality = self::DEFAULT_QUALITY,
    ): void {
        try {
            $this->save(
                $this->manager()->decodePath($source)->cover($width, $height, $this->alignment($position)),
                $target,
                $quality
            );
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    public function savePadded(
        string $source,
        string $target,
        int $width,
        int $height,
        string $background = 'ffffff',
        int $quality = self::DEFAULT_QUALITY,
    ): void {
        try {
            $this->save(
                $this->manager()->decodePath($source)->contain(
                    $width,
                    $height,
                    // The library dropped the string "transparent" in v4; the CMS keeps a name
                    // for it so that callers need not know a type of the library to ask for it.
                    $background === self::TRANSPARENT ? Color::transparent() : $background
                ),
                $target,
                $quality
            );
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    public function saveStretched(
        string $source,
        string $target,
        int $width,
        int $height,
        int $quality = self::DEFAULT_QUALITY,
    ): void {
        try {
            $this->save(
                $this->manager()->decodePath($source)->resize($width, $height),
                $target,
                $quality
            );
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    public function saveWatermarked(
        string $source,
        string $target,
        string $watermark,
        ImagePosition $position = ImagePosition::BottomRight,
        int $opacity = 100,
        int $offset = 0,
        int $quality = self::DEFAULT_QUALITY,
    ): void {
        if ($opacity < 0 || $opacity > 100) {
            throw new ImageProcessingException('The opacity of a watermark must be in range 0 to 100.');
        }

        try {
            $this->save(
                $this->manager()->decodePath($source)->insert(
                    $watermark,
                    $offset,
                    $offset,
                    $this->alignment($position),
                    // The library counts the other way round, in a fraction of full opacity.
                    $opacity / 100
                ),
                $target,
                $quality
            );
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    public function saveBlurredThumbnail(
        string $source,
        string $target,
        int $width,
        int $height,
        int $quality = self::DEFAULT_QUALITY,
    ): void {
        try {
            $image = $this->manager()->decodePath($source);

            // The foreground is built first, from a copy: the modifiers of the library change
            // the image in place, so cropping the backdrop would take the foreground with it.
            // Cloning is deep and saves decoding the file a second time.
            $foreground = (clone $image)->scaleDown($width, $height);

            $this->save(
                $image->cover($width, $height)
                    ->blur(self::BACKDROP_BLUR)
                    ->insert($foreground, alignment: Alignment::CENTER),
                $target,
                $quality
            );
        } catch (InterventionException $exception) {
            throw $this->failure($source, $exception);
        }
    }

    /**
     * The encoding format comes from the extension of the target path.
     *
     * The quality is passed by name on purpose: the library matches the options against the
     * parameters of the encoder it picked, so a positional argument is silently dropped and
     * the picture is written at the default quality instead.
     */
    private function save(ImageInterface $image, string $target, int $quality): void
    {
        $image->save($target, quality: $quality);
    }

    /**
     * The position of the CMS translated into the one of the library. Written out rather than
     * mapped by value: the two enums are free to disagree, and a renamed case of the library
     * has to break here instead of silently moving a watermark to another corner.
     */
    private function alignment(ImagePosition $position): Alignment
    {
        return match ($position) {
            ImagePosition::TopLeft => Alignment::TOP_LEFT,
            ImagePosition::Top => Alignment::TOP,
            ImagePosition::TopRight => Alignment::TOP_RIGHT,
            ImagePosition::Left => Alignment::LEFT,
            ImagePosition::Center => Alignment::CENTER,
            ImagePosition::Right => Alignment::RIGHT,
            ImagePosition::BottomLeft => Alignment::BOTTOM_LEFT,
            ImagePosition::Bottom => Alignment::BOTTOM,
            ImagePosition::BottomRight => Alignment::BOTTOM_RIGHT,
        };
    }

    private function failure(string $source, InterventionException $exception): ImageProcessingException
    {
        return new ImageProcessingException(
            sprintf('Could not process the image "%s": %s', $source, $exception->getMessage()),
            previous: $exception
        );
    }

    private function manager(): ImageManager
    {
        return $this->manager ??= new ImageManager(
            extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class,
            // A picture uploaded from a phone carries its orientation in the EXIF instead of in
            // the pixels; without this it is stored lying on its side. Needs ext-exif, and
            // degrades to no rotation when the extension is missing.
            autoOrientation: true,
            // The CMS stores a single frame of everything it is given. Decoding an animation
            // would mean resizing and blurring every frame of it, only to write the first one.
            decodeAnimation: false,
            // Visitors upload photos straight from a camera: the metadata of one carries the
            // GPS coordinates of where it was taken, and the stored file is public.
            strip: true,
        );
    }
}
