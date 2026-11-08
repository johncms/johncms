<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

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
            $this->date($collection->getRawOriginal('updated_at')),
            $collection->owner_module,
            $collection->declaration_key
        );
    }

    /** @param iterable<CollectionSectionDTO> $sections All the sections of one collection. */
    public function sectionTree(iterable $sections): SectionTree
    {
        $parents = $names = $sorts = $codes = $active = [];
        foreach ($sections as $section) {
            $parents[$section->id] = $section->parent ?? 0;
            $names[$section->id] = $section->name;
            $sorts[$section->id] = $section->sort;
            $codes[$section->id] = $section->code;
            $active[$section->id] = $section->active;
        }
        return new SectionTree(parents: $parents, names: $names, sorts: $sorts, codes: $codes, active: $active);
    }

    public function section(ContentCollectionSection $section): CollectionSectionDTO
    {
        return new CollectionSectionDTO(
            $section->id,
            $section->collection_id,
            $section->parent,
            $section->code,
            $section->name,
            $section->description,
            $section->active,
            $section->sort,
            $section->metadata ?? [],
            $this->date($section->getRawOriginal('created_at')),
            $this->date($section->getRawOriginal('updated_at'))
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

    private function date(string|DateTimeInterface|null $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
        }
        return $value === null || $value === '' ? null : new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
