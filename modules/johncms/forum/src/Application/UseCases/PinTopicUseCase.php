<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class PinTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId, bool $pinned): void
    {
        $this->topicRepository->setPinned($topicId, $pinned);
    }
}
