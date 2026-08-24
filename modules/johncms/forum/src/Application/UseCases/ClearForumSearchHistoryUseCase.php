<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Domain\Repository\ForumSearchHistoryRepositoryInterface;

final readonly class ClearForumSearchHistoryUseCase
{
    public function __construct(
        private ForumSearchHistoryRepositoryInterface $searchHistoryRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(): void
    {
        if (! $this->currentUser->isValid()) {
            return;
        }

        $this->searchHistoryRepository->clearForUser($this->currentUser->id());
    }
}
