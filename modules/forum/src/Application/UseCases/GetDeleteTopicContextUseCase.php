<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\DeleteTopicContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\DeleteTopicNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetDeleteTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): DeleteTopicContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new DeleteTopicNotFoundException('Topic not found.');
        }

        return new DeleteTopicContextDTO(
            topicId: (int) $topic->id,
            sectionId: (int) $topic->section_id,
        );
    }
}
