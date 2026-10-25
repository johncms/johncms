<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Johncms\Modules\News\Domain\Models\NewsArticle;

interface NewsArticleRepositoryInterface
{
    public function findById(int $id): ?NewsArticle;

    public function delete(NewsArticle $article): void;

    /**
     * @param list<int> $sectionIds
     */
    public function deleteInSections(array $sectionIds): void;
}
