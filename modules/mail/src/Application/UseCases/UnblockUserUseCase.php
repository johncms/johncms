<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;

final readonly class UnblockUserUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Unblock a user.
     *
     * @param int $userIdToUnblock User ID to unblock
     */
    public function execute(int $userIdToUnblock): void
    {
        $this->contactRepository->unblockUser($this->currentUser->id(), $userIdToUnblock);
    }
}
