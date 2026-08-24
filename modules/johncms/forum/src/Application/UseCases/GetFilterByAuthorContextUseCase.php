<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetFilterByAuthorContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): ForumTopic
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumValidationException('Topic not found.');
        }

        return $topic;
    }
}
