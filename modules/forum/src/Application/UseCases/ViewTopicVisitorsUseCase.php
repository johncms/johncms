<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\ForumVisitorsQueryDTO;
use Johncms\Modules\Forum\Application\DTO\ForumVisitorsResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumVisitorRowMapper;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;

final readonly class ViewTopicVisitorsUseCase
{
    public function __construct(
        private ForumWhoRepositoryInterface $whoRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumVisitorRowMapper $visitorRowMapper,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $topicId, ForumVisitorsQueryDTO $query): ForumVisitorsResultDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        $limit = (int) $this->currentUser->user()->config->kmess;
        $total = $query->guests
            ? $this->whoRepository->countTopicGuests($topicId)
            : $this->whoRepository->countTopicUsers($topicId);
        $start = $this->normalizeStart($query->start, $total, $limit);
        $items = [];

        if ($total > 0) {
            $rows = $query->guests
                ? $this->whoRepository->getTopicGuests($topicId, $start, $limit)
                : $this->whoRepository->getTopicUsers($topicId, $start, $limit);

            $items = $this->visitorRowMapper->map($rows, withPlace: false);
        }

        return new ForumVisitorsResultDTO($items, $total, $start);
    }

    private function normalizeStart(int $start, int $total, int $limit): int
    {
        if ($start < $total) {
            return max(0, $start);
        }

        if ($total === 0) {
            return 0;
        }

        $rest = $total % $limit;

        return max(0, $total - ($rest === 0 ? $limit : $rest));
    }
}
