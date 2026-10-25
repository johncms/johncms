<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;

final class EloquentNewsSectionRepository implements NewsSectionRepositoryInterface
{
    public function findById(int $id): ?NewsSection
    {
        return NewsSection::query()->find($id);
    }

    public function getParents(): array
    {
        return NewsSection::query()
            ->pluck('parent', 'id')
            ->map(static fn (?int $parent): int => $parent ?? 0)
            ->all();
    }

    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        NewsSection::query()->whereIn('id', $ids)->delete();
    }
}
