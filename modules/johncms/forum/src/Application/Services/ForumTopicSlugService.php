<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Illuminate\Support\Str;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class ForumTopicSlugService
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
    ) {
    }

    public function generateUniqueSlug(string $name, int $sectionId, ?int $excludeTopicId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'topic';
        }

        $slug = $baseSlug;
        $suffix = 2;
        while ($this->topicRepository->existsBySectionAndSlug($sectionId, $slug, $excludeTopicId)) {
            $slug = $baseSlug . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}
