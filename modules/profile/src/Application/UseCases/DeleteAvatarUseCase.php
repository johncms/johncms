<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

final readonly class DeleteAvatarUseCase
{
    public function execute(int $userId): void
    {
        $avatar = UPLOAD_PATH . 'users/avatar/' . $userId . '.png';
        if (is_file($avatar)) {
            @unlink($avatar);
        }
    }
}
