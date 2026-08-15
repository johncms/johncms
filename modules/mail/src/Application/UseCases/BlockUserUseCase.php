<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;

final readonly class BlockUserUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Block a user.
     *
     * @param int $userIdToBlock User ID to block
     * @throws \InvalidArgumentException if trying to block self
     */
    public function execute(int $userIdToBlock): void
    {
        if ($userIdToBlock === $this->currentUser->id()) {
            throw new \InvalidArgumentException(__('You cannot block yourself'));
        }

        $this->contactRepository->blockUser($this->currentUser->id(), $userIdToBlock);
    }
}
