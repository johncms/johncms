<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Exception;
use Intervention\Image\ImageManager;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;
use Psr\Http\Message\UploadedFileInterface;

final readonly class UploadPhotoUseCase
{
    public function __construct(
        private ImageManager $imageManager,
    ) {
    }

    public function execute(int $userId, UploadedFileInterface $file): void
    {
        $maxKb = (int) config('johncms')['flsz'];
        if ($file->getSize() > 1024 * $maxKb) {
            throw new ImageUploadException(__('The weight of the file exceeds') . ' ' . $maxKb . 'kb.');
        }

        try {
            $photo = UPLOAD_PATH . 'users/photo/' . $userId . '.jpg';
            $smallPhoto = UPLOAD_PATH . 'users/photo/' . $userId . '_small.jpg';

            $this->imageManager->make($file->getStream())
                ->resize(
                    1024,
                    960,
                    static function ($constraint): void {
                        /** @var \Intervention\Image\Constraint $constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                )
                ->save($photo, 100, 'jpg');

            $this->imageManager->make($file->getStream())
                ->resize(
                    400,
                    300,
                    static function ($constraint): void {
                        /** @var \Intervention\Image\Constraint $constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                )
                ->save($smallPhoto, 100, 'jpg');
        } catch (Exception $exception) {
            throw new ImageUploadException($exception->getMessage());
        }
    }
}
