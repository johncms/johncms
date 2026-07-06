<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;

final readonly class SaveCollectionItemUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $itemRepository,
        private ContentCollectionFieldRepositoryInterface $fieldRepository,
        private ContentCollectionItemValueRepositoryInterface $valueRepository,
    ) {
    }

    /**
     * Creates or updates an item and rebuilds its custom field values. Returns
     * true when an existing item was updated.
     *
     * @throws CollectionItemCodeAlreadyExistsException when the code belongs to another item in the same section
     */
    public function execute(?int $id, CollectionItemFormDTO $dto): bool
    {
        $existing = $this->itemRepository->findByCode($dto->collectionId, $dto->sectionId, $dto->code);
        if ($existing !== null && $existing->id !== $id) {
            throw new CollectionItemCodeAlreadyExistsException();
        }

        $attributes = [
            'collection_id' => $dto->collectionId,
            'section_id'    => $dto->sectionId,
            'code'          => $dto->code,
            'name'          => $dto->name,
            'active'        => $dto->active,
            'active_from'   => $dto->activeFrom,
            'active_to'     => $dto->activeTo,
            'sort'          => $dto->sort,
            'preview_text'  => $dto->previewText,
            'detail_text'   => $dto->detailText,
        ];

        if ($id !== null && $this->itemRepository->findById($id) !== null) {
            $this->itemRepository->update($id, $attributes);
            $itemId = $id;
            $isUpdate = true;
        } else {
            $itemId = $this->itemRepository->create($attributes)->id;
            $isUpdate = false;
        }

        $this->rebuildValues($itemId, $dto);

        return $isUpdate;
    }

    private function rebuildValues(int $itemId, CollectionItemFormDTO $dto): void
    {
        // Full replace: the form submits the complete set of values.
        $this->valueRepository->deleteByItem($itemId);

        $rows = [];
        foreach ($this->fieldRepository->getByCollection($dto->collectionId) as $field) {
            $rows = array_merge($rows, $this->rowsForField($itemId, $field, $dto->values[$field->code] ?? null));
        }

        $this->valueRepository->insertMany($rows);
    }

    /**
     * @param string|list<string>|null $raw
     * @return list<array<string, mixed>>
     */
    private function rowsForField(int $itemId, ContentCollectionField $field, string|array|null $raw): array
    {
        $column = $field->type->valueColumn();

        if ($field->multiple) {
            $rows = [];
            $sort = 0;
            foreach (is_array($raw) ? $raw : [] as $value) {
                $prepared = $this->prepareValue($field->type, (string) $value);
                if ($prepared !== null) {
                    $rows[] = $this->row($itemId, $field->id, $sort++, $column, $prepared);
                }
            }

            return $rows;
        }

        $prepared = $this->prepareValue($field->type, is_array($raw) ? '' : (string) ($raw ?? ''));

        return $prepared !== null ? [$this->row($itemId, $field->id, 0, $column, $prepared)] : [];
    }

    /**
     * Converts a raw submitted string to the value stored in the typed column, or
     * null when the value is empty and should not be persisted.
     */
    private function prepareValue(FieldType $type, string $raw): int|float|string|null
    {
        $raw = trim($raw);

        // Boolean is always persisted (0 or 1); other empty values are skipped.
        if ($type === FieldType::Boolean) {
            return $raw !== '' && $raw !== '0' ? 1 : 0;
        }

        if ($raw === '') {
            return null;
        }

        return match ($type) {
            FieldType::Integer, FieldType::Relation => (int) $raw,
            FieldType::Double                       => (float) $raw,
            FieldType::Date, FieldType::Datetime    => $this->normalizeDate($raw),
            default                                 => $raw,
        };
    }

    private function normalizeDate(string $raw): ?string
    {
        $timestamp = strtotime(str_replace('T', ' ', $raw));

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(int $itemId, int $fieldId, int $sort, string $column, int|float|string $value): array
    {
        return [
            'item_id'      => $itemId,
            'field_id'     => $fieldId,
            'value_string' => null,
            'value_int'    => null,
            'value_double' => null,
            'value_date'   => null,
            'value_text'   => null,
            'sort'         => $sort,
            $column        => $value,
        ];
    }
}
