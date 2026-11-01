<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;

final class ContentCollectionItemValueRepository implements ContentCollectionItemValueRepositoryInterface
{
    public function deleteByItem(int $itemId): void
    {
        ContentCollectionItemValue::query()->where('item_id', $itemId)->delete();
    }

    public function deleteFields(int $itemId, array $fieldIds): void
    {
        ContentCollectionItemValue::query()->where('item_id', $itemId)->whereIn('field_id', $fieldIds)->delete();
    }

    public function insertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        ContentCollectionItemValue::query()->insert($rows);
    }
}
