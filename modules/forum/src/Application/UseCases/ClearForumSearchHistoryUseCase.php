<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumSearchHistoryRepositoryInterface;
use Johncms\Users\User;

final readonly class ClearForumSearchHistoryUseCase
{
    public function __construct(
        private ForumSearchHistoryRepositoryInterface $searchHistoryRepository,
        private User $currentUser,
    ) {
    }

    public function execute(): void
    {
        if (! $this->currentUser->isValid()) {
            return;
        }

        $this->searchHistoryRepository->clearForUser((int) $this->currentUser->id);
    }
}
