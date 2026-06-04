<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\ResetSettingsContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetResetSettingsContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): ResetSettingsContextDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        // Only an admin (rights >= 7) may reset settings of a user with strictly lower rights
        if ($this->currentUser->rights < 7 || $this->currentUser->rights <= $profileUser->rights) {
            throw new ProfileAccessForbiddenException();
        }

        return new ResetSettingsContextDTO(
            profileUserId: $profileUser->id,
            profileUserName: $profileUser->name,
        );
    }
}
