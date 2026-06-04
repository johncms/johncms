<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

final readonly class DeletePhotoUseCase
{
    public function execute(int $userId): void
    {
        $photo = UPLOAD_PATH . 'users/photo/' . $userId . '.jpg';
        $smallPhoto = UPLOAD_PATH . 'users/photo/' . $userId . '_small.jpg';
        if (is_file($photo)) {
            @unlink($photo);
        }
        if (is_file($smallPhoto)) {
            @unlink($smallPhoto);
        }
    }
}
