<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
use DateTimeZone;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;

final class CollectionApiMapper
{
    public function collection(ContentCollection $collection): CollectionRecordDTO
    {
        return new CollectionRecordDTO(
            $collection->id,
            $collection->code,
            $collection->name,
            $collection->description,
            $collection->active,
            $collection->public,
            $collection->sort,
            $collection->settings ?? [],
            $this->date($collection->getRawOriginal('created_at')),
            $this->date($collection->getRawOriginal('updated_at'))
        );
    }

    public function item(ContentCollectionItem $item): CollectionItemDTO
    {
        $values = [];
        foreach ($item->values as $value) {
            $field = $value->field;
            if ($field === null) {
                continue;
            }
            $raw = $value->getRawOriginal($field->type->valueColumn());
            $typed = in_array($field->type, [FieldType::Date, FieldType::Datetime], true)
                ? $this->date($raw) : $field->type->cast($raw);
            if ($field->multiple) {
                $values[$field->code][] = $typed;
            } else {
                $values[$field->code] = $typed;
            }
        }
        return new CollectionItemDTO(
            $item->id,
            $item->collection_id,
            $item->section_id,
            $item->code,
            $item->name,
            $item->active,
            $this->date($item->getRawOriginal('active_from')),
            $this->date($item->getRawOriginal('active_to')),
            $item->sort,
            $item->preview_text,
            $item->detail_text,
            $item->view_count ?? 0,
            $item->created_by,
            $item->updated_by,
            $this->date($item->getRawOriginal('created_at')),
            $this->date($item->getRawOriginal('updated_at')),
            $values
        );
    }

    private function date(string|\DateTimeInterface|null $value): ?DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
        }
        return $value === null || $value === '' ? null : new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
