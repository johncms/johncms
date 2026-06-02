<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\ChangePasswordContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetChangePasswordContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): ChangePasswordContextDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        // The password may be changed by the owner, or by an admin (rights >= 7) over a user with lower rights
        $isSelf = $profileUser->id === $this->currentUser->id;
        if (! $isSelf && ($this->currentUser->rights < 7 || $profileUser->rights > $this->currentUser->rights)) {
            throw new ProfileAccessForbiddenException();
        }

        return new ChangePasswordContextDTO(
            profileUserId: $profileUser->id,
            profileUserName: $profileUser->name,
            isSelf: $isSelf,
        );
    }
}
