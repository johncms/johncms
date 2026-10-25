<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;

interface NewsSectionRepositoryInterface
{
    public function findById(int $id): ?NewsSection;

    /**
     * Where every section stands in the tree.
     *
     * @return array<int, int> The id of a section => the id of its parent, 0 at the root.
     */
    public function getParents(): array;

    /**
     * The names of every section.
     *
     * @return array<int, string> The id of a section => its name.
     */
    public function getNames(): array;

    public function countAll(): int;

    /**
     * The sections the list lets through.
     *
     * @param list<int>|null $ids The sections it may show, worked out by the caller from the tree;
     *                            null for any.
     */
    public function countList(NewsListFilter $filter, ?array $ids): int;

    /**
     * One page of them, each with the number of the articles standing in it.
     *
     * @param list<int>|null $ids As for countList().
     * @return Collection<int, NewsSection>
     */
    public function getList(NewsListFilter $filter, ?array $ids, int $limit, int $offset): Collection;

    /**
     * @param list<int> $ids
     */
    public function deleteByIds(array $ids): void;
}
