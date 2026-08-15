<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\DTO\ResetSettingsContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

final readonly class GetResetSettingsContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(int $userId): ResetSettingsContextDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($profileUser === null || (! $profileUser->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        // Settings are reset for somebody standing below: wiping the settings of a peer is not
        // what the permission is for, and the roles are what say who stands where.
        $outranksTarget = $this->roleLevels->highestGrantedTo($profileUser->id)
            < $this->roleLevels->highest($this->currentUser->identity());

        if (! $this->accessChecker->allows(ProfilePermissions::SETTINGS_RESET) || ! $outranksTarget) {
            throw new ProfileAccessForbiddenException();
        }

        return new ResetSettingsContextDTO(
            profileUserId: $profileUser->id,
            profileUserName: $profileUser->name,
        );
    }
}
