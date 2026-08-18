<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Http\UploadedFileDTO;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;

final readonly class UploadPhotoUseCase
{
    private const int PHOTO_WIDTH = 1024;
    private const int PHOTO_HEIGHT = 960;
    private const int THUMB_WIDTH = 400;
    private const int THUMB_HEIGHT = 300;

    public function __construct(
        private ImageProcessorInterface $imageProcessor,
    ) {
    }

    public function execute(int $userId, UploadedFileDTO $file): void
    {
        $maxKb = (int) config('johncms')['flsz'];
        if ($file->size > 1024 * $maxKb) {
            throw new ImageUploadException(__('The weight of the file exceeds') . ' ' . $maxKb . 'kb.');
        }

        try {
            $this->imageProcessor->saveScaledDown(
                $file->tmpPath,
                UPLOAD_PATH . 'users/photo/' . $userId . '.jpg',
                self::PHOTO_WIDTH,
                self::PHOTO_HEIGHT
            );

            $this->imageProcessor->saveScaledDown(
                $file->tmpPath,
                UPLOAD_PATH . 'users/photo/' . $userId . '_small.jpg',
                self::THUMB_WIDTH,
                self::THUMB_HEIGHT
            );
        } catch (ImageProcessingException $exception) {
            throw new ImageUploadException($exception->getMessage());
        }
    }
}
