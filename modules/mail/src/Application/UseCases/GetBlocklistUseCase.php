<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Users\User;

final readonly class GetBlocklistUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private User $user,
    ) {
    }

    /**
     * Get blocked users list for the current user.
     *
     * @return array
     */
    public function execute(): array
    {
        $blocklist = $this->contactRepository->getBlocklist($this->user->id);

        return [
            'blocklist' => $blocklist,
            'total' => $blocklist->count(),
        ];
    }
}
