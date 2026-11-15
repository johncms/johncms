<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\LatestTopicsResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicRows;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class ViewLatestTopicsUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicRows $topicRows,
    ) {
    }

    public function execute(): LatestTopicsResultDTO
    {
        $topics = $this->topicRows->present(rows: $this->topicRepository->getLatest(10), withPlace: true);

        return new LatestTopicsResultDTO($topics, count($topics));
    }
}
