<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final readonly class GetBanFormContextUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $targetId): User
    {
        $target = $this->profileUserRepository->findById($targetId);

        if ($target === null || (! $target->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        // Only staff above the target may ban; a moderator (rights < 6) cannot ban anyone with rights
        if (
            $this->currentUser->rights < 1
            || ($this->currentUser->rights < 6 && $target->rights)
            || ($this->currentUser->rights <= $target->rights)
        ) {
            throw new ProfileAccessForbiddenException(__('You do not have enought rights to ban this user'));
        }

        return $target;
    }
}
