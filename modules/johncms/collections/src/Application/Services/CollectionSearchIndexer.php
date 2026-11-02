<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\CollectionSearchRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\CollectionTextNormalizer;

final readonly class CollectionSearchIndexer implements CollectionChangeHandlerInterface
{
    public function __construct(
        private CollectionSearchRepositoryInterface $index,
        private CollectionTransactionInterface $transaction,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionItemRepositoryInterface $items,
        private ItemContentFormatter $content,
    ) {
    }

    public function withinTransaction(CollectionChangeDTO $change): void
    {
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
        if ($change->entity === 'item') {
            $this->rebuildItem($change->collectionId, $change->id);
        } elseif (in_array($change->entity, ['collection', 'field'], true)) {
            $this->rebuildCollection($change->collectionId);
        }
    }

    public function rebuildAll(): void
    {
        foreach ($this->index->collectionIds() as $id) {
            $this->rebuildCollection($id);
        }
    }

    public function rebuildCollection(int $collectionId): void
    {
        $this->transaction->run(function () use ($collectionId): void {
            $collection = $this->collections->findForUpdate($collectionId);
            $this->index->clearCollection($collectionId);
            if ($collection === null) {
                return;
            }
            $after = 0;
            while (($ids = $this->index->itemIds($collectionId, $after, 100)) !== []) {
                foreach ($ids as $id) {
                    $this->writeItem($collectionId, $id);
                    $after = $id;
                }
            }
        });
    }

    public function rebuildItem(int $collectionId, int $itemId): void
    {
        $this->transaction->run(function () use ($collectionId, $itemId): void {
            $this->collections->findForUpdate($collectionId);
            $this->writeItem($collectionId, $itemId);
        });
    }

    private function writeItem(int $collectionId, int $itemId): void
    {
        $item = $this->items->findWithValues($itemId);
        $documents = [];
        if ($item !== null && $item->collection_id === $collectionId) {
            foreach (['name', 'preview_text', 'detail_text'] as $attribute) {
                $documents['item.' . $attribute] = $this->plain($item->getAttribute($attribute) ?? '');
            }
            foreach ($item->values as $value) {
                $field = $value->field;
                if ($field !== null && $field->collection_id === $collectionId && in_array($field->type, [FieldType::String_, FieldType::Text, FieldType::Html], true)) {
                    $reference = 'field.' . $field->code;
                    $documents[$reference] = trim(($documents[$reference] ?? '') . ' ' . $this->plain($value->getAttribute($field->type->valueColumn()) ?? ''));
                }
            }
        }
        $this->index->replace($collectionId, $itemId, $documents);
    }

    private function plain(string $value): string
    {
        return CollectionTextNormalizer::normalize($this->content->plain($value));
    }
}
