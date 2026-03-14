<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\PinTopicNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetPinTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): int
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new PinTopicNotFoundException('Topic not found.');
        }

        return (int) $topic->id;
    }
}
