<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\DTO\EditProfileContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetEditContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId): EditProfileContextDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        $isSelf = $profileUser->id === $this->currentUser->id;

        // The profile may be edited by its owner, or by an admin (rights >= 7) over a user with no higher rights
        if (! $isSelf && ($this->currentUser->rights < 7 || $profileUser->rights > $this->currentUser->rights)) {
            throw new ProfileAccessForbiddenException(__('You cannot edit profile of higher administration'));
        }

        // A banned editor may not change any profile
        if (! empty($this->currentUser->ban)) {
            throw new ProfileAccessForbiddenException();
        }

        return new EditProfileContextDTO(
            profileUser: $profileUser,
            isSelf: $isSelf,
            canEditAdminFields: $this->currentUser->rights >= 7,
        );
    }
}
