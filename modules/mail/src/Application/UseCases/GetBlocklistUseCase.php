<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;

final readonly class GetBlocklistUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Get blocked users list for the current user.
     *
     * @return array
     */
    public function execute(): array
    {
        $blocklist = $this->contactRepository->getBlocklist($this->currentUser->id());

        return [
            'blocklist' => $blocklist,
            'total' => $blocklist->count(),
        ];
    }
}
