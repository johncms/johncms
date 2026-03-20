<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\AddVoteContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetAddVoteContextUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(int $topicId): AddVoteContextDTO
    {
        $topic = $this->topicRepository->findActiveById($topicId);
        if ($topic === null) {
            throw new ForumValidationException('Topic not found.');
        }

        return new AddVoteContextDTO($topic->id);
    }
}
