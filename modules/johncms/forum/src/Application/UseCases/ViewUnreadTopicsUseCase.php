<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\UnreadTopicsQueryDTO;
use Johncms\Modules\Forum\Application\DTO\UnreadTopicsResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumTopicRows;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class ViewUnreadTopicsUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicRows $topicRows,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(UnreadTopicsQueryDTO $query): UnreadTopicsResultDTO
    {
        $includeDeleted = $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
        $total = $this->topicRepository->countUnreadForUser($this->currentUser->id(), $includeDeleted);
        if ($total === 0) {
            return new UnreadTopicsResultDTO([], 0);
        }

        $rows = $this->topicRepository->getUnreadForUser(
            userId: $this->currentUser->id(),
            includeDeleted: $includeDeleted,
            start: $query->start,
            limit: (int) $this->currentUser->user()->config->kmess,
        );

        return new UnreadTopicsResultDTO($this->topicRows->present(rows: $rows, withPlace: true, allUnread: true), $total);
    }
}
