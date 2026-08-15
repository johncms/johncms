<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\ForumVisitorsQueryDTO;
use Johncms\Modules\Forum\Application\DTO\ForumVisitorsResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumVisitorRowMapper;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;

final readonly class ViewForumVisitorsUseCase
{
    public function __construct(
        private ForumWhoRepositoryInterface $whoRepository,
        private ForumVisitorRowMapper $visitorRowMapper,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(ForumVisitorsQueryDTO $query): ForumVisitorsResultDTO
    {
        $limit = (int) $this->currentUser->user()->config->kmess;
        $total = $query->guests
            ? $this->whoRepository->countForumGuests()
            : $this->whoRepository->countForumUsers();
        $start = $this->normalizeStart($query->start, $total, $limit);
        $items = [];

        if ($total > 0) {
            $rows = $query->guests
                ? $this->whoRepository->getForumGuests($start, $limit)
                : $this->whoRepository->getForumUsers($start, $limit);

            $items = $this->visitorRowMapper->map($rows, withPlace: true);
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
