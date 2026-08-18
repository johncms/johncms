<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Http\UploadedFileDTO;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Album\Application\Exceptions\ImageUploadException;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

final readonly class UploadPhotoUseCase
{
    private const MAX_DESCRIPTION_LENGTH = 1500;
    private const ORIGINAL_WIDTH = 1920;
    private const ORIGINAL_HEIGHT = 1080;
    private const THUMB_WIDTH = 400;
    private const THUMB_HEIGHT = 300;

    public function __construct(
        private ImageProcessorInterface $imageProcessor,
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
    }

    public function execute(Album $album, UploadedFileDTO $file, string $description): void
    {
        $maxKb = (int) config('johncms')['flsz'];
        if ($file->size > 1024 * $maxKb) {
            throw new ImageUploadException(__('The weight of the file exceeds') . ' ' . $maxKb . 'kb.');
        }

        $dir = UPLOAD_PATH . 'users/album/' . $album->user_id . '/';
        if (! is_dir($dir) && ! mkdir($dir, 0777) && ! is_dir($dir)) {
            throw new ImageUploadException(__('An error occurred'));
        }

        $time = time();
        $originalFile = 'img_' . $time . '.jpg';
        $thumbFile = 'tmb_' . $time . '.jpg';

        try {
            // The original, scaled down to fit within the maximum bounds.
            $this->imageProcessor->saveScaledDown(
                $file->tmpPath,
                $dir . $originalFile,
                self::ORIGINAL_WIDTH,
                self::ORIGINAL_HEIGHT
            );

            $this->imageProcessor->saveBlurredThumbnail(
                $file->tmpPath,
                $dir . $thumbFile,
                self::THUMB_WIDTH,
                self::THUMB_HEIGHT
            );
        } catch (ImageProcessingException $exception) {
            throw new ImageUploadException($exception->getMessage());
        }

        $this->photoRepository->create(
            albumId: $album->id,
            userId: $album->user_id,
            imgName: $originalFile,
            tmbName: $thumbFile,
            description: mb_substr($description, 0, self::MAX_DESCRIPTION_LENGTH),
            time: $time,
            access: (int) $album->access,
        );
    }
}
