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

use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Http\UploadedFileDTO;
use Johncms\Files\StoredFileDTO;

/**
 * Stores a picture the text editor uploaded, within the limits the site has set.
 *
 * Every editor on the site uploads through this: the forum, the guestbook, the comments of the
 * news and the article form of the admin panel. They used to hold a copy of the same code each,
 * which is how the guestbook ended up storing a ten-megabyte photo at its full resolution while
 * the forum checked nothing either.
 *
 * What it does with an upload, in order: refuses anything that is not a picture, refuses one
 * heavier than the limit, and scales down what is larger than the bounds — re-encoding it into
 * the format the site stores. A picture already within the bounds is stored as it came: there is
 * nothing to gain from re-encoding it, and re-encoding costs quality.
 */
final readonly class StoreEditorImageUseCase
{
    /**
     * The pictures the editor accepts. Checked against the *contents* of the upload rather than
     * against its name: an extension is what the visitor typed, and the name reaches the disk.
     */
    private const array ALLOWED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];

    public function __construct(
        private FileStore $files,
        private ImageProcessorInterface $imageProcessor,
        private EditorImageSettings $settings,
    ) {
    }

    /**
     * @param string $directory Directory of the disk to store under, e.g. `forum_files`.
     * @throws EditorImageUploadException when the visitor may not store this file.
     * @throws FileStoreException when the file could not be stored.
     */
    public function execute(UploadedFileDTO $upload, string $directory): StoredFileDTO
    {
        $this->ensureUploadSucceeded($upload);
        $this->ensureWithinSizeLimit($upload);

        $picture = $this->readPicture($upload);

        // An animated GIF is stored as it came: scaling it down would leave a single frame, and
        // a still picture in place of the animation is not what the visitor uploaded.
        if ($picture->type === IMAGETYPE_GIF) {
            return $this->files->storeUpload($upload, $directory);
        }

        $format = $this->settings->format();
        $extension = $format->extension($picture->type);
        $scaleDown = $this->exceedsBounds($picture);

        if (! $scaleDown && $format === EditorImageFormat::Original) {
            return $this->files->storeUpload($upload, $directory);
        }

        $processed = $this->process($upload, $extension, $scaleDown);

        try {
            return $this->files->storeLocalFile($processed, $directory, $this->targetName($upload, $extension));
        } finally {
            // The store copied the file onto the disk of the site; the temporary one is of no
            // use to anybody after that, and it is removed even when the store failed.
            $this->discard($processed);
        }
    }

    /**
     * Write the stored copy of the picture into a temporary file and return its path.
     *
     * @throws EditorImageUploadException
     */
    private function process(UploadedFileDTO $upload, string $extension, bool $scaleDown): string
    {
        $target = $this->temporaryPath($extension);

        try {
            if ($scaleDown) {
                $this->imageProcessor->saveScaledDown(
                    $upload->tmpPath,
                    $target,
                    $this->settings->maxWidth(),
                    $this->settings->maxHeight(),
                    $this->settings->quality(),
                );
            } else {
                // Nothing to scale, only the format changes: the picture is re-encoded at its
                // own size.
                $this->imageProcessor->saveConverted($upload->tmpPath, $target, $this->settings->quality());
            }
        } catch (ImageProcessingException $exception) {
            $this->discard($target);

            throw new EditorImageUploadException(d__('system', 'Error uploading file'), 0, $exception);
        }

        return $target;
    }

    /**
     * @throws EditorImageUploadException
     */
    private function ensureUploadSucceeded(UploadedFileDTO $upload): void
    {
        if ($upload->isValid()) {
            return;
        }

        // The two size errors are the ones a visitor can do something about, so they are named
        // as such instead of arriving as "the upload failed".
        if ($upload->error === UPLOAD_ERR_INI_SIZE || $upload->error === UPLOAD_ERR_FORM_SIZE) {
            throw new EditorImageUploadException($this->tooHeavyMessage());
        }

        throw new EditorImageUploadException(d__('system', 'Error uploading file'));
    }

    /**
     * @throws EditorImageUploadException
     */
    private function ensureWithinSizeLimit(UploadedFileDTO $upload): void
    {
        if (($upload->size ?? 0) > $this->settings->maxSizeBytes()) {
            throw new EditorImageUploadException($this->tooHeavyMessage());
        }
    }

    /**
     * The size and the type of the upload, read from its contents.
     *
     * @throws EditorImageUploadException when the file is not a picture the site accepts.
     */
    private function readPicture(UploadedFileDTO $upload): EditorImageInfo
    {
        $size = @getimagesize($upload->tmpPath);
        if ($size === false || ! in_array($size[2], self::ALLOWED_TYPES, true)) {
            throw new EditorImageUploadException(d__('system', 'Only images are allowed'));
        }

        return new EditorImageInfo(width: $size[0], height: $size[1], type: $size[2]);
    }

    private function exceedsBounds(EditorImageInfo $picture): bool
    {
        $maxWidth = $this->settings->maxWidth();
        $maxHeight = $this->settings->maxHeight();

        return ($maxWidth !== null && $picture->width > $maxWidth)
            || ($maxHeight !== null && $picture->height > $maxHeight);
    }

    /**
     * The name the file is registered under: the one the visitor uploaded, carrying the
     * extension of the format it is actually stored in.
     */
    private function targetName(UploadedFileDTO $upload, string $extension): string
    {
        $name = pathinfo((string) $upload->clientName, PATHINFO_FILENAME);

        return ($name === '' ? 'image' : $name) . '.' . $extension;
    }

    private function temporaryPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'johncms_image_');
        if ($path === false) {
            throw new EditorImageUploadException(d__('system', 'Error uploading file'));
        }

        // The processor takes the format from the extension, and tempnam() creates a file
        // without one; the empty placeholder it made is removed with it.
        unlink($path);

        return $path . '.' . $extension;
    }

    private function discard(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function tooHeavyMessage(): string
    {
        return sprintf(d__('system', 'The file is larger than %d KB'), $this->settings->maxSizeKb());
    }
}
