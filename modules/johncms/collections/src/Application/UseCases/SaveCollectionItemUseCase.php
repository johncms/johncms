<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Illuminate\Support\Str;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Updates\SystemOperationContext;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Services\CollectionItemValidator;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
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
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionSectionRepositoryInterface $sections,
        private CollectionTransactionInterface $transaction,
        private CollectionItemValidator $validator,
        private SystemOperationContext $systemContext = new SystemOperationContext(),
    ) {
    }

    /**
     * Creates or updates an item and rebuilds its custom field values. Returns
     * the item id. A non-null id must belong to an existing item in this collection.
     *
     * @param list<string>|null $valueFields Null replaces all values; a list changes only those fields.
     * @param list<string> $clearFields
     * @throws CollectionItemCodeAlreadyExistsException when an explicit code belongs to another item in the same section
     */
    public function execute(?int $id, CollectionItemFormDTO $dto, ?int $actorId = null, ?CollectionItemHistoryDTO $history = null, ?array $valueFields = null, array $clearFields = []): int
    {
        if ($id === null && $valueFields !== null) {
            throw new \InvalidArgumentException('Partial value writes require an existing item.');
        }
        if ($history !== null) {
            $this->systemContext->requireActive();
            if ($id !== null) {
                throw new \InvalidArgumentException('Historical import creates new records only.');
            }
        }
        return $this->transaction->run(function () use ($id, $dto, $actorId, $history, $valueFields, $clearFields): int {
            // Serialize writes, including root codes which a nullable unique index cannot protect.
            $collection = $this->collections->findForUpdate($dto->collectionId);
            if ($collection === null) {
                throw new CollectionItemFormException(['collection_id' => [d__('collections', 'Collection not found.')]]);
            }
            if ($id !== null) {
                $item = $this->itemRepository->findForUpdate($id);
                if ($item === null || $item->collection_id !== $dto->collectionId) {
                    throw new CollectionItemFormException(['id' => [d__('collections', 'The item does not belong to this collection or no longer exists.')]]);
                }
            }
            if ($dto->sectionId !== null) {
                $section = $this->sections->findForUpdate($dto->sectionId);
                if ($section === null || $section->collection_id !== $dto->collectionId) {
                    throw new CollectionItemFormException(['section_id' => [d__('collections', 'Choose a section of this collection.')]]);
                }
            }
            if ($actorId !== null && $actorId < 1) {
                throw new CollectionItemFormException(['actor_id' => [d__('collections', 'Choose a valid author or use a system operation.')]]);
            }
            $definitions = $this->fieldRepository->getByCollection($dto->collectionId)->keyBy('code');
            foreach ([...($valueFields ?? []), ...$clearFields] as $code) {
                if (! isset($definitions[$code])) {
                    throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'Unknown collection field.')]]);
                }
            }
            foreach ($clearFields as $code) {
                if ($definitions[$code]->required || ($valueFields !== null && ! in_array($code, $valueFields, true))) {
                    throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'This field cannot be cleared.')]]);
                }
            }
            $dto = $this->validator->validate($dto, $definitions->values());
            $attributes = [
                'collection_id' => $dto->collectionId, 'section_id' => $dto->sectionId,
                'code' => $this->resolveCode($id, $dto), 'name' => $dto->name,
                'active' => $dto->active, 'active_from' => $dto->activeFrom, 'active_to' => $dto->activeTo,
                'sort' => $dto->sort, 'preview_text' => $dto->previewText, 'detail_text' => $dto->detailText,
            ];
            if ($id !== null) {
                $attributes['updated_by'] = $actorId;
                $this->itemRepository->update($id, $attributes);
                $itemId = $id;
            } else {
                $attributes['created_by'] = $actorId;
                if ($history !== null) {
                    $attributes = array_replace($attributes, $history->attributes());
                }
                $itemId = $this->itemRepository->create($attributes)->id;
            }
            $this->rebuildValues($itemId, $dto, $valueFields, $clearFields);
            return $itemId;
        });
    }

    /**
     * Resolves the code to persist. An explicit code must be unique within the
     * collection/section (a duplicate is rejected). An empty code is generated as
     * a slug of the name and made unique by appending a numeric suffix on collision.
     *
     * @throws CollectionItemCodeAlreadyExistsException
     */
    private function resolveCode(?int $id, CollectionItemFormDTO $dto): string
    {
        if ($dto->code !== '') {
            $existing = $this->itemRepository->findByCodeForUpdate($dto->collectionId, $dto->sectionId, $dto->code);
            if ($existing !== null && $existing->id !== $id) {
                throw new CollectionItemCodeAlreadyExistsException();
            }

            return $dto->code;
        }

        $base = mb_substr(Str::slug($dto->name), 0, 240);
        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 1;
        while (true) {
            $existing = $this->itemRepository->findByCodeForUpdate($dto->collectionId, $dto->sectionId, $candidate);
            if ($existing === null || $existing->id === $id) {
                return $candidate;
            }

            $candidate = $base . '-' . ++$suffix;
        }
    }

    /** @param list<string>|null $valueFields
     * @param list<string> $clearFields
     */
    private function rebuildValues(int $itemId, CollectionItemFormDTO $dto, ?array $valueFields, array $clearFields): void
    {
        $fields = $this->fieldRepository->getByCollection($dto->collectionId);
        if ($valueFields === null) {
            $this->valueRepository->deleteByItem($itemId);
        } else {
            $fields = $fields->filter(static fn (ContentCollectionField $field): bool => in_array($field->code, $valueFields, true));
            $this->valueRepository->deleteFields($itemId, $fields->pluck('id')->all());
        }
        $rows = [];
        foreach ($fields as $field) {
            if (! in_array($field->code, $clearFields, true)) {
                $rows = array_merge($rows, $this->rowsForField($itemId, $field, $dto->values[$field->code] ?? null));
            }
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
            FieldType::Date, FieldType::Datetime    => $raw,
            default                                 => $raw,
        };
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
