<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Exception;
use Intervention\Image\ImageManager;
use Johncms\Modules\Album\Application\Exceptions\ImageUploadException;
use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Http\UploadedFileDTO;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

final readonly class UploadPhotoUseCase
{
    private const MAX_DESCRIPTION_LENGTH = 1500;
    private const ORIGINAL_WIDTH = 1920;
    private const ORIGINAL_HEIGHT = 1080;
    private const THUMB_WIDTH = 400;
    private const THUMB_HEIGHT = 300;

    public function __construct(
        private ImageManager $imageManager,
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
            // Save the original, scaled down to fit within the maximum bounds.
            $img = $this->imageManager->make($file->tmpPath)
                ->resize(
                    self::ORIGINAL_WIDTH,
                    self::ORIGINAL_HEIGHT,
                    static function ($constraint): void {
                        /** @var \Intervention\Image\Constraint $constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );
            $img->save($dir . $originalFile, 100, 'jpg');

            // Build the thumbnail: a blurred, cropped backdrop with the scaled image centered on top.
            $resized = $this->imageManager->make($file->tmpPath)
                ->resize(
                    self::THUMB_WIDTH,
                    self::THUMB_HEIGHT,
                    static function ($constraint): void {
                        /** @var \Intervention\Image\Constraint $constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );

            $thumb = $img->fit(self::THUMB_WIDTH, self::THUMB_HEIGHT)
                ->blur(20)
                ->insert($resized, 'center');
            $thumb->save($dir . $thumbFile, 100, 'jpg');
        } catch (Exception $exception) {
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
