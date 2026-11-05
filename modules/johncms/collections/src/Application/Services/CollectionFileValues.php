<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Updates\SystemOperationContext;

/** File values and schema defaults have references separate from ordinary text attachments. */
final readonly class CollectionFileValues implements CollectionChangeHandlerInterface
{
    public function __construct(
        private CollectionFileRepositoryInterface $links,
        private ContentCollectionFieldRepositoryInterface $fields,
        private \Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface $values,
        private CollectionTransactionInterface $transaction,
        private CollectionItemFiles $attachments,
        private FileStore $files,
        private CollectionFilePolicy $policy,
        private SystemOperationContext $system,
    ) {
    }

    /** @param Collection<int, ContentCollectionField> $fields
     * @return array<int, list<int>>
     */
    public function prepare(CollectionItemFormDTO $form, Collection $fields, ?int $itemId, ?int $actorId): array
    {
        $available = $this->links->availableForCollection($form->collectionId);
        if ($form->uploadContext !== null) {
            $available = [...$available, ...$this->attachments->contextFiles($form->uploadContext, $form->collectionId, $itemId, $actorId)];
        }
        $prepared = [];
        foreach ($fields as $field) {
            if ($field->type === FieldType::File && ($field->settings['file_storage'] ?? 'legacy') === 'registry') {
                $prepared[$field->id] = $this->validate($field, $form->values[$field->code] ?? [], $available);
            }
        }
        return $prepared;
    }

    /** @param array<int, list<int>> $prepared */
    public function synchronize(int $itemId, array $prepared): void
    {
        foreach ($prepared as $fieldId => $ids) {
            $removed = $this->links->replaceValueLinks($fieldId, $itemId, $ids);
            $this->transaction->afterCommit(fn () => $this->attachments->removeUnreferenced($removed));
        }
    }

    public function withinTransaction(CollectionChangeDTO $change): void
    {
        if ($change->entity !== 'field') {
            return;
        }
        $field = $this->fields->findForUpdate($change->id);
        if ($change->action === 'deleted' || $field === null || $field->type !== FieldType::File || ($field->settings['file_storage'] ?? 'legacy') !== 'registry') {
            $removed = $this->links->unlinkField($change->id);
        } else {
            try {
                $available = $this->links->availableForCollection($change->collectionId);
                $ids = $this->validate($field, $field->settings['default'] ?? [], $available);
                $groups = $this->values->getFieldValuesForUpdate($field->id)->groupBy('item_id');
                $itemIds = array_unique([...$this->links->fieldItems($field->id), ...$groups->keys()->all()]);
                foreach ($itemIds as $itemId) {
                    $rows = $groups->get($itemId);
                    if ($rows !== null && $rows->contains(static fn ($row): bool => $row->item->collection_id !== $change->collectionId)) {
                        throw $this->invalid($field);
                    }
                    $references = $this->validate($field, $rows?->pluck('value_string')->all() ?? [], $available);
                    $old = $this->links->replaceValueLinks($field->id, $itemId, $references);
                    $this->transaction->afterCommit(fn () => $this->attachments->removeUnreferenced($old));
                }
            } catch (CollectionItemFormException $exception) {
                throw new \Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException(implode(" ", array_merge(...array_values($exception->errors))), 0, $exception);
            }
            $removed = $this->links->replaceValueLinks($field->id, null, $ids);
        }
        $this->transaction->afterCommit(fn () => $this->attachments->removeUnreferenced($removed));
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
    }

    /** @param string|list<string> $raw
     * @param list<int> $available
     * @return list<int>
     */
    private function validate(ContentCollectionField $field, string|array $raw, array $available): array
    {
        $ids = [];
        foreach (is_array($raw) ? $raw : [$raw] as $value) {
            if ($value === '') {
                continue;
            }
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false || (! $this->system->isActive() && ! in_array($id, $available, true))) {
                throw $this->invalid($field);
            }
            $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        foreach ($ids as $id) {
            $this->links->register($id, false);
            $this->links->lockAsset($id);
            if (! $this->files->isRegistered($id)) {
                throw $this->invalid($field);
            }
            $this->policy->assertStored($id, $field->code, $field->settings ?? []);
        }
        return $ids;
    }

    private function invalid(ContentCollectionField $field): CollectionItemFormException
    {
        return new CollectionItemFormException(['values.' . $field->code => [d__('collections', 'Choose a registered file of this collection or upload it in your current form.')]]);
    }
}
