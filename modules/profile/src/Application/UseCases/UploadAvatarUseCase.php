<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Exception;
use Intervention\Image\ImageManager;
use Johncms\Http\UploadedFileDTO;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;

final readonly class UploadAvatarUseCase
{
    public function __construct(
        private ImageManager $imageManager,
    ) {
    }

    public function execute(int $userId, UploadedFileDTO $file): void
    {
        $maxKb = (int) config('johncms')['flsz'];
        if ($file->size > 1024 * $maxKb) {
            throw new ImageUploadException(__('The weight of the file exceeds') . ' ' . $maxKb . 'kb.');
        }

        try {
            $avatar = UPLOAD_PATH . 'users/avatar/' . $userId . '.png';
            $this->imageManager->make($file->tmpPath)
                ->resize(
                    150,
                    150,
                    static function ($constraint): void {
                        /** @var \Intervention\Image\Constraint $constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                )
                ->save($avatar, 100, 'png');
        } catch (Exception $exception) {
            throw new ImageUploadException($exception->getMessage());
        }
    }
}
