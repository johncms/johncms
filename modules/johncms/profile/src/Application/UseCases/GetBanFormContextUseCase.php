<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\Bans\BanAccess;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetBanFormContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private AccessCheckerInterface $accessChecker,
        private BanAccess $banAccess,
    ) {
    }

    public function execute(int $targetId): User
    {
        $target = $this->profileUserRepository->findById($targetId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($target === null || (! $target->preg && ! $this->accessChecker->allows(CorePermissions::USERS_UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        if (! $this->banAccess->mayBanAnything() || ! $this->banAccess->outranks($target->id)) {
            throw new ProfileAccessForbiddenException(__('You do not have enought rights to ban this user'));
        }

        return $target;
    }
}
