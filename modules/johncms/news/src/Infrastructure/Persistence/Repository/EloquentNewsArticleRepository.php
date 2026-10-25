<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;

final class EloquentNewsArticleRepository implements NewsArticleRepositoryInterface
{
    public function findById(int $id): ?NewsArticle
    {
        return NewsArticle::query()->find($id);
    }

    public function delete(NewsArticle $article): void
    {
        $article->delete();
    }

    public function deleteInSections(array $sectionIds): void
    {
        if ($sectionIds === []) {
            return;
        }

        NewsArticle::query()->whereIn('section_id', $sectionIds)->delete();
    }
}
