<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Users\UserImages;

final readonly class DeletePhotoUseCase
{
    public function __construct(
        private UserImages $userImages,
    ) {
    }

    public function execute(int $userId): void
    {
        $this->userImages->deletePhoto($userId);
    }
}
