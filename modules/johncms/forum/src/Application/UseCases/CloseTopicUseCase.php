<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class CloseTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId, bool $closed): void
    {
        $this->topicRepository->setClosed($topicId, $closed);
    }
}
