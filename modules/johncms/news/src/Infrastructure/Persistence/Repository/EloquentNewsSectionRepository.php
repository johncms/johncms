<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\Repository\NewsSectionRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;

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

    public function getNames(): array
    {
        return NewsSection::query()->pluck('name', 'id')->all();
    }

    public function getAllWithArticleCounts(): Collection
    {
        return NewsSection::query()->withCount('articles')->get();
    }

    public function countAll(): int
    {
        return NewsSection::query()->count();
    }

    public function isCodeTaken(string $code, int $parentId, ?int $exceptId = null): bool
    {
        $query = NewsSection::query()->where('code', $code);

        if ($parentId === NewsListFilter::ROOT) {
            $query->where(static fn (Builder $query) => $query->where('parent', 0)->orWhereNull('parent'));
        } else {
            $query->where('parent', $parentId);
        }

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    public function create(array $attributes): NewsSection
    {
        return NewsSection::query()->create($attributes);
    }

    public function update(NewsSection $section, array $attributes): void
    {
        $section->update($attributes);
    }

    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        NewsSection::query()->whereIn('id', $ids)->delete();
    }
}
