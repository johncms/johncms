<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class UpdateCuratorsUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    /**
     * @param array<int, string> $curators
     */
    public function execute(ForumTopic $topic, array $curators): void
    {
        $topic->curators = $curators;
        $this->topicRepository->save($topic);
    }
}
