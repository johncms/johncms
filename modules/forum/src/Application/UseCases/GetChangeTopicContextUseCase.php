<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\ChangeTopicContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ChangeTopicNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetChangeTopicContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): ChangeTopicContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ChangeTopicNotFoundException('Topic not found.');
        }

        return new ChangeTopicContextDTO($topic);
    }
}
