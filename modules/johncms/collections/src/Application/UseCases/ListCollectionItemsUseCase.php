<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;

/**
 * The items of a collection in the panel, whatever their window, narrowed by the filter, and what
 * the list around them needs: the tree of the sections and the fields shown as its columns.
 *
 * The section of the filter is turned into the ids of its subtree here: the repository only ever
 * sees a list of ids.
 */
final readonly class ListCollectionItemsUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $repository,
        private ContentCollectionSectionRepositoryInterface $sections,
        private ContentCollectionFieldRepositoryInterface $fields,
    ) {
    }

    public function sectionTree(int $collectionId): SectionTree
    {
        return SectionTree::of($this->sections->getAllByCollection($collectionId));
    }

    /**
     * The fields the collection shows as columns of the list.
     *
     * @return list<ContentCollectionField>
     */
    public function listedFields(int $collectionId): array
    {
        return $this->fields->getByCollection($collectionId)
            ->filter(static fn (ContentCollectionField $field): bool => $field->in_list)
            ->values()
            ->all();
    }

    public function count(int $collectionId, ItemListFilter $filter, SectionTree $tree, ?DateTimeInterface $now = null): int
    {
        return $this->repository->countForPanel($collectionId, $filter, $this->sectionIds($filter, $tree), $now ?? new DateTimeImmutable());
    }

    /**
     * @return Collection<int, ContentCollectionItem>
     */
    public function getPage(int $collectionId, ItemListFilter $filter, SectionTree $tree, int $limit, int $offset, ?DateTimeInterface $now = null): Collection
    {
        return $this->repository->getForPanel($collectionId, $filter, $this->sectionIds($filter, $tree), $now ?? new DateTimeImmutable(), $limit, $offset);
    }

    /**
     * The items of a section stand in it or under it; those without one, outside every section.
     *
     * @return list<int>|null
     */
    private function sectionIds(ItemListFilter $filter, SectionTree $tree): ?array
    {
        return match ($filter->section) {
            null                            => null,
            ItemListFilter::WITHOUT_SECTION => [ItemListFilter::WITHOUT_SECTION],
            default                         => $tree->subtreeOf($filter->section),
        };
    }
}
