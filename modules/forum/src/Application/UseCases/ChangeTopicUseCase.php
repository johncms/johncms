<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Application\Services\ForumTopicSlugService;

final readonly class ChangeTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicSlugService $topicSlugService,
    ) {
    }

    public function execute(
        ForumTopic $topic,
        string $name,
        ?string $metaKeywords,
        ?string $metaDescription,
    ): void {
        $topic->name = $name;
        if ($topic->slug === null || trim($topic->slug) === '') {
            $topic->slug = $this->topicSlugService->generateUniqueSlug($name, $topic->section_id, $topic->id);
        }
        $topic->meta_keywords = $metaKeywords;
        $topic->meta_description = $metaDescription;

        $this->topicRepository->save($topic);
    }
}
