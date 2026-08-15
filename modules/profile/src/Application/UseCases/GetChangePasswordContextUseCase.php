<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\DTO\ChangePasswordContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

final readonly class GetChangePasswordContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(int $userId): ChangePasswordContextDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($profileUser === null || (! $profileUser->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        // The password is changed by the owner, or by whoever holds the permission and does not
        // stand below them
        $isSelf = $profileUser->id === $this->currentUser->id();
        if (
            ! $isSelf
            && (
                ! $this->accessChecker->allows(ProfilePermissions::PASSWORD_CHANGE)
                || $this->roleLevels->highestGrantedTo($profileUser->id) > $this->roleLevels->highest($this->currentUser->identity())
            )
        ) {
            throw new ProfileAccessForbiddenException();
        }

        return new ChangePasswordContextDTO(
            profileUserId: $profileUser->id,
            profileUserName: $profileUser->name,
            isSelf: $isSelf,
        );
    }
}
