<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\CollectionSearchDocument;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\CollectionSearchRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\CollectionPublicationQuery;

final readonly class CollectionSearchRepository implements CollectionSearchRepositoryInterface
{
    public function __construct(private CollectionPublicationQuery $publication = new CollectionPublicationQuery())
    {
    }

    public function replace(int $collectionId, int $itemId, array $documents): void
    {
        CollectionSearchDocument::query()->where('item_id', $itemId)->delete();
        foreach ($documents as $reference => $text) {
            if ($text !== '') {
                CollectionSearchDocument::query()->create(['collection_id' => $collectionId, 'item_id' => $itemId, 'reference' => $reference, 'plain_text' => $text]);
            }
        }
    }

    public function clearCollection(int $collectionId): void
    {
        CollectionSearchDocument::query()->where('collection_id', $collectionId)->delete();
    }

    public function collectionIds(): array
    {
        return ContentCollection::query()->orderBy('id')->pluck('id')->all();
    }

    public function itemIds(int $collectionId, int $afterId, int $limit): array
    {
        return ContentCollectionItem::query()->where('collection_id', $collectionId)->where('id', '>', $afterId)->orderBy('id')->limit($limit)->pluck('id')->all();
    }

    public function suggestTags(int $collectionId, string $fieldCode, string $prefix, int $limit, bool $published): array
    {
        $field = ContentCollectionField::query()->where('collection_id', $collectionId)->where('code', $fieldCode)->first();
        if ($field === null || $field->type !== FieldType::String_ || ! $field->multiple || $field->role !== 'tags') {
            throw new InvalidArgumentException('Tag suggestions require a multiple string field with the tags role.');
        }
        /** @var Builder<ContentCollectionItem> $items */
        $items = ContentCollectionItem::query()->select('collection_items.id')->where('collection_id', $collectionId);
        if ($published) {
            $this->publication->apply($items, $collectionId, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        }
        $like = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $prefix) . '%';
        return Capsule::connection()->table('collection_item_values')->where('field_id', $field->id)
            ->whereIn('item_id', $items->toBase())->whereNotNull('value_string')->where('value_string', '<>', '')
            ->whereRaw("value_string LIKE ? ESCAPE '!'", [$like])->distinct()->orderBy('value_string')->limit($limit)->pluck('value_string')->all();
    }
}
