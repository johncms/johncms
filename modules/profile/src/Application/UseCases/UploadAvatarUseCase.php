<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Http\UploadedFileDTO;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;

final readonly class UploadAvatarUseCase
{
    private const int AVATAR_WIDTH = 150;
    private const int AVATAR_HEIGHT = 150;

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
                UPLOAD_PATH . 'users/avatar/' . $userId . '.png',
                self::AVATAR_WIDTH,
                self::AVATAR_HEIGHT
            );
        } catch (ImageProcessingException $exception) {
            throw new ImageUploadException($exception->getMessage());
        }
    }
}
