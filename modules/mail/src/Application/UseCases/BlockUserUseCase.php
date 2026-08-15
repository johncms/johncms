<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Users\User;

final readonly class BlockUserUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private User $user,
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
        if ($userIdToBlock === $this->user->id) {
            throw new \InvalidArgumentException(__('You cannot block yourself'));
        }

        $this->contactRepository->blockUser($this->user->id, $userIdToBlock);
    }
}
