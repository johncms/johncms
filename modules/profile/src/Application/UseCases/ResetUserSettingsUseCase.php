<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

final readonly class ResetUserSettingsUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
    ) {
    }

    public function execute(int $userId): void
    {
        $this->profileUserRepository->resetSettings($userId);
    }
}
