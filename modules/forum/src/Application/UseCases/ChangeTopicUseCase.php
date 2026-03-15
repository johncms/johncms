<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class ChangeTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function execute(
        ForumTopic $topic,
        string $name,
        ?string $metaKeywords,
        ?string $metaDescription,
    ): void {
        $topic->name = $name;
        $topic->meta_keywords = $metaKeywords;
        $topic->meta_description = $metaDescription;

        $this->topicRepository->save($topic);
    }
}
