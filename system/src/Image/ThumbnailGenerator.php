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
 * Previews of stored pictures, built once and kept on disk.
 *
 * A preview used to be produced on every single view: a page listing twenty attachments
 * decoded, scaled and blurred twenty pictures, every time anybody opened it. Here the result
 * is written under the cache directory and reused until the source file changes.
 *
 * The cache sits outside the document root on purpose. A preview is only ever served by a
 * controller, which is what gets to decide whether this visitor may see the picture at all;
 * a directory the web server hands out directly could not.
 *
 * Removed wholesale by `php system/bin/console cache:clear`, like every other cache.
 */
final readonly class ThumbnailGenerator
{
    /**
     * Previews are decoration, not the stored picture: the few percent of quality nobody sees
     * are worth the bytes on a page that shows a whole grid of them.
     */
    private const int QUALITY = 85;

    /** The formats a preview is written in; anything else is written as JPEG. */
    private const array PRESERVED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private ImageProcessorInterface $processor,
        private string $directory = CACHE_PATH . 'thumbnails',
    ) {
    }

    /**
     * A copy of the source that fits within the given bounds, in the format of the source.
     *
     * @return string Path of the cached file.
     * @throws ImageProcessingException
     */
    public function scaledDown(string $source, int $width, int $height): string
    {
        return $this->cached(
            $source,
            'scaled-' . $width . 'x' . $height,
            $this->outputExtension($source),
            function (string $target) use ($source, $width, $height): void {
                $this->processor->saveScaledDown($source, $target, $width, $height, self::QUALITY);
            }
        );
    }

    /**
     * A tile of exactly the given size: the source centered on a blurred backdrop of itself.
     *
     * Always a JPEG — the backdrop fills the tile, so there is nothing transparent left to
     * keep.
     *
     * @return string Path of the cached file.
     * @throws ImageProcessingException
     */
    public function blurredBackdrop(string $source, int $width, int $height): string
    {
        return $this->cached(
            $source,
            'backdrop-' . $width . 'x' . $height,
            'jpg',
            function (string $target) use ($source, $width, $height): void {
                $this->processor->saveBlurredThumbnail($source, $target, $width, $height, self::QUALITY);
            }
        );
    }

    /**
     * @param callable(string): void $generate Writes the preview to the path it is given.
     * @throws ImageProcessingException
     */
    private function cached(string $source, string $variant, string $extension, callable $generate): string
    {
        $sourcePath = realpath($source);
        if ($sourcePath === false || ! is_file($sourcePath)) {
            throw new ImageProcessingException(sprintf('The image "%s" does not exist.', $source));
        }

        $path = $this->path($sourcePath, $variant, $extension);

        // A preview older than the picture it was made from belongs to a file that has since
        // been replaced under the same name.
        if (is_file($path) && filemtime($path) >= filemtime($sourcePath)) {
            return $path;
        }

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new ImageProcessingException(sprintf('Could not create the preview directory "%s".', $directory));
        }

        // Written aside and moved into place: two visitors asking for the same missing preview
        // both generate it, and a half-written file must never be the one that is served.
        // The extension has to survive, it is what the format of the output is taken from.
        $temporary = $path . '.' . uniqid('', true) . '.' . $extension;
        $generate($temporary);

        if (! rename($temporary, $path)) {
            @unlink($temporary);
            throw new ImageProcessingException(sprintf('Could not store the preview at "%s".', $path));
        }

        return $path;
    }

    /**
     * Two levels of subdirectories: a site with a busy forum accumulates previews by the
     * thousand, and a single flat directory of them is slow to read on most filesystems.
     */
    private function path(string $sourcePath, string $variant, string $extension): string
    {
        $hash = sha1($variant . '|' . $sourcePath);

        return $this->directory
            . DS . substr($hash, 0, 2)
            . DS . substr($hash, 2, 2)
            . DS . $hash . '.' . $extension;
    }

    private function outputExtension(string $source): string
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        return in_array($extension, self::PRESERVED_EXTENSIONS, true) ? $extension : 'jpg';
    }
}
