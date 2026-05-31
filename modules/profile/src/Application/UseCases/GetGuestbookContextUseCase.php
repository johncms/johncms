<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetGuestbookContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): User
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        return $profileUser;
    }
}
