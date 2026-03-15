<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\NewMessageContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\NewMessageTopicNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetNewMessageContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): NewMessageContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new NewMessageTopicNotFoundException('Topic not found.');
        }

        return new NewMessageContextDTO($topic);
    }
}
