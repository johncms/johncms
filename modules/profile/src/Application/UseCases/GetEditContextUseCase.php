<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\DTO\EditProfileContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetEditContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private CurrentUser $currentUser,
        private CurrentUser $identity,
        private AccessCheckerInterface $accessChecker,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(int $userId): EditProfileContextDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($profileUser === null || (! $profileUser->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        $isSelf = $profileUser->id === $this->currentUser->id();
        $mayEditOthers = $this->accessChecker->allows(ProfilePermissions::PROFILE_EDIT);
        $ownLevel = $this->roleLevels->highest($this->identity->identity());
        $targetLevel = $this->roleLevels->highestGrantedTo($profileUser->id);

        // Somebody else's profile is edited by whoever holds the permission and does not stand
        // below its owner
        if (! $isSelf && (! $mayEditOthers || $targetLevel > $ownLevel)) {
            throw new ProfileAccessForbiddenException(__('You cannot edit profile of higher administration'));
        }

        // A banned editor may not change any profile
        if (! empty($this->currentUser->user()->ban)) {
            throw new ProfileAccessForbiddenException();
        }

        return new EditProfileContextDTO(
            profileUser: $profileUser,
            isSelf: $isSelf,
            canEditAdminFields: $mayEditOthers,
            canResetSettings: $this->accessChecker->allows(ProfilePermissions::SETTINGS_RESET) && $targetLevel < $ownLevel,
        );
    }
}
