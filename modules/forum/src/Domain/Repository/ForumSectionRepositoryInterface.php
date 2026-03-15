<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Johncms\Modules\Forum\Domain\Models\ForumSection;

interface ForumSectionRepositoryInterface
{
    public function findById(int $sectionId): ?ForumSection;

    /**
     * @return ForumSection[]
     */
    public function getTopicSectionsByCategory(int $categoryId, int $excludeSectionId): array;

    /**
     * @return ForumSection[]
     */
    public function getOtherCategories(int $excludeCategoryId): array;
}
