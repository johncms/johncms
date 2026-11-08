<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Query;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

/** The SQL publication scope shared by lists, details, counters and sitemap. */
final class CollectionPublicationQuery
{
    /**
     * @param Builder<ContentCollectionItem> $builder
     * @param SectionTree|null $sections The sections of the collection when the caller has them;
     *                                   read here otherwise, and always when locking.
     */
    public function apply(Builder $builder, int $collectionId, DateTimeImmutable $at, bool $current = false, ?SectionTree $sections = null): void
    {
        $now = $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $visible = ($current || $sections === null ? $this->read($collectionId, $current) : $sections)->visibleIds();
        $builder->where('collection_id', $collectionId)->where('active', true);
        if ($current) {
            /** @var ContentCollection|null $collection */
            $collection = ContentCollection::query()->where('id', $collectionId)->lockForUpdate()->first();
            if ($collection === null || ! $collection->active) {
                $builder->whereRaw('1 = 0');
            }
        } else {
            $builder->whereHas('collection', static fn (Builder $collection) => $collection->where('active', true));
        }
        $builder
            ->where(static fn (Builder $query) => $query->whereNull('active_from')->orWhere('active_from', '<=', $now))
            ->where(static fn (Builder $query) => $query->whereNull('active_to')->orWhere('active_to', '>=', $now))
            ->where(static fn (Builder $query) => $query->whereNull('section_id')->orWhereIn('section_id', $visible));
    }

    private function read(int $collectionId, bool $lock): SectionTree
    {
        $sections = ContentCollectionSection::query()->where('collection_id', $collectionId);
        if ($lock) {
            $sections->lockForUpdate();
        }
        return SectionTree::of($sections->get(['id', 'parent', 'name', 'code', 'sort', 'active']));
    }
}
