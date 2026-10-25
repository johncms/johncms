<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\News\Domain\Enums\NewsListSort;
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

    public function countAll(): int
    {
        return NewsSection::query()->count();
    }

    public function countList(NewsListFilter $filter, ?array $ids): int
    {
        return $this->listQuery($filter, $ids)->count();
    }

    public function getList(NewsListFilter $filter, ?array $ids, int $limit, int $offset): Collection
    {
        $column = match ($filter->sort) {
            NewsListSort::ID       => 'id',
            NewsListSort::CREATED  => 'created_at',
            NewsListSort::UPDATED  => 'updated_at',
            NewsListSort::ARTICLES => 'articles_count',
            default                => 'name',
        };
        $direction = $filter->direction === SortDirection::ASC ? 'asc' : 'desc';

        return $this->listQuery($filter, $ids)
            ->withCount('articles')
            ->orderBy($column, $direction)
            ->orderBy('id', $direction)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        NewsSection::query()->whereIn('id', $ids)->delete();
    }

    /**
     * @param list<int>|null $ids
     * @return Builder<NewsSection>
     */
    private function listQuery(NewsListFilter $filter, ?array $ids): Builder
    {
        $query = NewsSection::query();

        if ($filter->search !== '') {
            $search = $filter->search;
            $query->where(static function (Builder $query) use ($search): void {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);

                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        return $query;
    }
}
