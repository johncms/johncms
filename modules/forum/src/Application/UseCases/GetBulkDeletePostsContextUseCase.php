<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\BulkDeletePostsContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\BulkDeleteTopicNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetBulkDeletePostsContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): BulkDeletePostsContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new BulkDeleteTopicNotFoundException('Topic not found.');
        }

        return new BulkDeletePostsContextDTO(
            topicId: $topic->id,
            backUrl: '/forum/?type=topic&id=' . $topic->id,
        );
    }
}
