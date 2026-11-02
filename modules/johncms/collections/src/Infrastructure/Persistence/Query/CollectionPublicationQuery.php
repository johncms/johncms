<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Query;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

/** The SQL publication scope shared by lists, details, counters and sitemap. */
final class CollectionPublicationQuery
{
    /** @param Builder<ContentCollectionItem> $builder */
    public function apply(Builder $builder, int $collectionId, DateTimeImmutable $at): void
    {
        $now = $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $tree = SectionTree::of(ContentCollectionSection::query()->where('collection_id', $collectionId)->get(['id', 'parent', 'name', 'code', 'sort', 'active']));
        $visible = $tree->visibleIds();
        $builder->where('collection_id', $collectionId)->where('active', true)
            ->whereHas('collection', static fn (Builder $collection) => $collection->where('active', true))
            ->where(static fn (Builder $query) => $query->whereNull('active_from')->orWhere('active_from', '<=', $now))
            ->where(static fn (Builder $query) => $query->whereNull('active_to')->orWhere('active_to', '>=', $now))
            ->where(static fn (Builder $query) => $query->whereNull('section_id')->orWhereIn('section_id', $visible));
    }
}
