<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Enums\NewsListSort;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\Users\User;

final class EloquentNewsArticleRepository implements NewsArticleRepositoryInterface
{
    public function findById(int $id): ?NewsArticle
    {
        return NewsArticle::query()->find($id);
    }

    public function countAll(): int
    {
        return NewsArticle::query()->count();
    }

    public function countList(NewsListFilter $filter, ?array $sectionIds, DateTimeInterface $now): int
    {
        return $this->listQuery($filter, $sectionIds, $now)->count();
    }

    public function getList(NewsListFilter $filter, ?array $sectionIds, DateTimeInterface $now, int $limit, int $offset): Collection
    {
        $column = match ($filter->sort) {
            NewsListSort::NAME    => 'name',
            NewsListSort::CREATED => 'created_at',
            NewsListSort::UPDATED => 'updated_at',
            NewsListSort::VIEWS   => 'view_count',
            default               => 'id',
        };
        $direction = $filter->direction === SortDirection::ASC ? 'asc' : 'desc';

        return $this->listQuery($filter, $sectionIds, $now)
            ->with('author')
            ->withCount('comments')
            ->orderBy($column, $direction)
            // The same moment or the same name twice keeps one order between the pages.
            ->orderBy('id', $direction)
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getAuthors(): Collection
    {
        return User::query()
            ->whereIn('id', NewsArticle::query()->select('created_by')->whereNotNull('created_by'))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function countInSections(array $sectionIds): int
    {
        if ($sectionIds === []) {
            return 0;
        }

        return NewsArticle::query()->whereIn('section_id', $sectionIds)->count();
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

    /**
     * @param list<int>|null $sectionIds
     * @return Builder<NewsArticle>
     */
    private function listQuery(NewsListFilter $filter, ?array $sectionIds, DateTimeInterface $now): Builder
    {
        $query = NewsArticle::query();

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

        if ($sectionIds !== null) {
            $query->where(static function (Builder $query) use ($sectionIds): void {
                $query->whereIn('section_id', $sectionIds);

                // The root holds the articles stored with 0 as well as those stored without one.
                if (in_array(NewsListFilter::ROOT, $sectionIds, true)) {
                    $query->orWhereNull('section_id');
                }
            });
        }

        if ($filter->status !== null) {
            $this->whereStatus($query, $filter->status, $now->format('Y-m-d H:i:s'));
        }

        if ($filter->author !== null) {
            $query->where('created_by', $filter->author);
        }

        return $query;
    }

    /**
     * The conditions of ArticleStatus::of(), as SQL.
     *
     * @param Builder<NewsArticle> $query
     */
    private function whereStatus(Builder $query, ArticleStatus $status, string $now): void
    {
        if ($status === ArticleStatus::DRAFT) {
            $query->where(static function (Builder $query): void {
                $query->whereNull('active')->orWhere('active', 0);
            });

            return;
        }

        $query->where('active', 1);

        match ($status) {
            ArticleStatus::EXPIRED   => $query->where('active_to', '<', $now),
            ArticleStatus::SCHEDULED => $query
                ->where(static fn (Builder $query) => $query->whereNull('active_to')->orWhere('active_to', '>=', $now))
                ->where('active_from', '>', $now),
            default                  => $query
                ->where(static fn (Builder $query) => $query->whereNull('active_to')->orWhere('active_to', '>=', $now))
                ->where(static fn (Builder $query) => $query->whereNull('active_from')->orWhere('active_from', '<=', $now)),
        };
    }
}
