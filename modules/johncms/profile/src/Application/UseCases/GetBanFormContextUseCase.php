<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Profile\Application\Access\BanAccess;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetBanFormContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private AccessCheckerInterface $accessChecker,
        private BanAccess $banAccess,
        private CurrentUser $currentUser,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(int $targetId): User
    {
        $target = $this->profileUserRepository->findById($targetId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($target === null || (! $target->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        // A ban goes downwards only: whoever holds one of the ban permissions may apply it to
        // somebody standing below them, never to a peer and never upwards.
        $outranksTarget = $this->roleLevels->highestGrantedTo($target->id)
            < $this->roleLevels->highest($this->currentUser->identity());

        if (! $this->banAccess->mayBanAnything() || ! $outranksTarget) {
            throw new ProfileAccessForbiddenException(__('You do not have enought rights to ban this user'));
        }

        return $target;
    }
}
