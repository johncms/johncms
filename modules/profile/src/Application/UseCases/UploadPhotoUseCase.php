<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Http\UploadedFileDTO;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;
use Johncms\Storage\StorageException;
use Johncms\Users\UserImages;

final readonly class UploadPhotoUseCase
{
    private const int PHOTO_WIDTH = 1024;
    private const int PHOTO_HEIGHT = 960;
    private const int THUMB_WIDTH = 400;
    private const int THUMB_HEIGHT = 300;

    public function __construct(
        private ImageProcessorInterface $imageProcessor,
        private UserImages $userImages,
    ) {
    }

    public function execute(int $userId, UploadedFileDTO $file): void
    {
        $maxKb = (int) config('johncms')['flsz'];
        if ($file->size > 1024 * $maxKb) {
            throw new ImageUploadException(__('The weight of the file exceeds') . ' ' . $maxKb . 'kb.');
        }

        try {
            $this->userImages->storePhoto(
                $userId,
                fn(string $target) => $this->imageProcessor->saveScaledDown(
                    $file->tmpPath,
                    $target,
                    self::PHOTO_WIDTH,
                    self::PHOTO_HEIGHT
                ),
                fn(string $target) => $this->imageProcessor->saveScaledDown(
                    $file->tmpPath,
                    $target,
                    self::THUMB_WIDTH,
                    self::THUMB_HEIGHT
                )
            );
        } catch (ImageProcessingException | StorageException $exception) {
            throw new ImageUploadException($exception->getMessage());
        }
    }
}
