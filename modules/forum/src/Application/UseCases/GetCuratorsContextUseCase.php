<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\CuratorsContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetCuratorsContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumMessageRepositoryInterface $messageRepository,
    ) {
    }

    public function execute(int $topicId): CuratorsContextDTO
    {
        $topic = $this->topicRepository->findById($topicId);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        $candidates = $this->messageRepository->getTopicCuratorCandidates($topicId);

        return new CuratorsContextDTO(
            topic: $topic,
            candidates: $candidates,
        );
    }
}
