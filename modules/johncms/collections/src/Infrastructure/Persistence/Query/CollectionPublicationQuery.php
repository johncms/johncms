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
    /** @param Builder<ContentCollectionItem> $builder */
    public function apply(Builder $builder, int $collectionId, DateTimeImmutable $at, bool $current = false): void
    {
        $now = $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $sections = ContentCollectionSection::query()->where('collection_id', $collectionId);
        if ($current) {
            $sections->lockForUpdate();
        }
        $tree = SectionTree::of($sections->get(['id', 'parent', 'name', 'code', 'sort', 'active']));
        $visible = $tree->visibleIds();
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
}
