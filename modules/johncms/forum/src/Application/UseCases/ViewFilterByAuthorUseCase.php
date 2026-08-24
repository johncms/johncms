<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\FilterByAuthorContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class ViewFilterByAuthorUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumMessageRepositoryInterface $messageRepository,
    ) {
    }

    public function execute(int $topicId): FilterByAuthorContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumValidationException('Topic not found.');
        }

        $authors = $this->messageRepository->getTopicAuthorFilterOptions($topicId);
        if ($authors === []) {
            throw new ForumValidationException('Topic has no authors to filter.');
        }

        return new FilterByAuthorContextDTO(
            topic: $topic,
            authors: $authors,
        );
    }
}
