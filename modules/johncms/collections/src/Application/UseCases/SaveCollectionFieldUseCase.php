<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionFieldSettingsValidator;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Updates\SystemOperationContext;

final readonly class SaveCollectionFieldUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
        private CollectionTransactionInterface $transaction,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionItemRepositoryInterface $items,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
        private SystemOperationContext $context = new SystemOperationContext(),
        private CollectionFieldSettingsValidator $settingsValidator = new CollectionFieldSettingsValidator(),
    ) {
    }

    public function execute(?int $id, CollectionFieldFormDTO $dto, ?CollectionFieldDeclarationDTO $owned = null): int
    {
        return $this->transaction->run(function () use ($id, $dto, $owned): int {
            $collection = $this->collections->findForUpdate($dto->collectionId);
            if ($collection === null) {
                throw new CollectionSchemaException(d__('collections', 'Collection not found.'));
            }
            $current = $id !== null ? $this->repository->findForUpdate($id) : null;
            if ($id !== null && ($current === null || $current->collection_id !== $dto->collectionId)) {
                throw new CollectionSchemaException(d__('collections', 'The field does not belong to this collection or no longer exists.'));
            }
            if ($owned !== null && (! $this->context->isActive() || $collection->owner_module === null)) {
                throw new CollectionSchemaException(d__('collections', 'Owned fields can only be prepared by a trusted update hook of an owned collection.'));
            }
            if ($owned !== null && $current !== null && $current->owner_module !== $collection->owner_module) {
                throw new CollectionSchemaException(d__('collections', 'A custom field cannot be adopted as an owner field. Rename it before updating.'));
            }
            if ($current?->owner_module !== null && ! $this->context->isActive()) {
                throw new CollectionSchemaException(d__('collections', 'This field is managed by its module. Change the declaration or use its trusted update hook.'));
            }
            if (
                ! preg_match('/^[a-z0-9_-]{1,255}$/D', $dto->code) || trim($dto->name) === '' || mb_strlen($dto->name) > 255
                || ($dto->role !== null && ! preg_match('/^[a-z0-9_]{1,255}$/D', $dto->role))
            ) {
                throw new CollectionSchemaException(d__('collections', 'Use a valid field code, label and role.'));
            }
            if ($dto->role === 'tags' && ($dto->type !== FieldType::String_ || ! $dto->multiple)) {
                throw new CollectionSchemaException(d__('collections', 'The tags role requires a multiple string field.'));
            }
            if ($dto->sort < -2147483648 || $dto->sort > 2147483647) {
                throw new CollectionSchemaException(d__('collections', 'Enter an integer between -2147483648 and 2147483647.'));
            }
            $before = $current !== null ? new CollectionChangeStateDTO(null, null, $current->code) : null;
            $existing = $this->repository->findByCode($dto->collectionId, $dto->code);
            if ($existing !== null && $existing->id !== $id) {
                throw new CollectionFieldCodeAlreadyExistsException();
            }
            $roleHolder = $dto->role !== null ? $this->repository->findByRole($dto->collectionId, $dto->role) : null;
            if ($roleHolder !== null && $roleHolder->id !== $id) {
                throw new CollectionSchemaException(d__('collections', 'Another field already uses this role.'));
            }
            if ($current !== null && ($current->type !== $dto->type || $current->multiple !== $dto->multiple) && $this->repository->hasValues($current->id) && ! $this->context->isActive()) {
                throw new CollectionSchemaException(d__('collections', 'Convert existing values explicitly before changing their type or multiplicity.'));
            }
            $settings = array_replace($current->settings ?? [], $dto->settings);
            if ($dto->type === FieldType::File) {
                $settings['file_storage'] ??= $current?->type === $dto->type ? 'legacy' : 'registry';
                if ($current?->type === $dto->type && ($current->settings['file_storage'] ?? 'legacy') !== $settings['file_storage'] && $this->repository->hasValues($current->id) && ! $this->context->isActive()) {
                    throw new CollectionSchemaException(d__('collections', 'Convert existing file values explicitly before changing their storage mode.'));
                }
                if ($current?->type !== FieldType::File && $settings['file_storage'] === 'legacy' && ! $this->context->isActive()) {
                    throw new CollectionSchemaException(d__('collections', 'New file fields must use registered files.'));
                }
            }
            $this->settingsValidator->validate($dto->type, $dto->multiple, $settings, $dto->required);
            if ($dto->type === FieldType::Relation) {
                $target = $settings['target_collection_id'];
                if ($this->collections->findForUpdate($target) === null) {
                    throw new CollectionSchemaException(d__('collections', 'Choose an existing target collection.'));
                }
                if ($current !== null && $current->type === $dto->type && ($current->settings['target_collection_id'] ?? null) !== $target && $this->repository->hasValues($current->id) && ! $this->context->isActive()) {
                    throw new CollectionSchemaException(d__('collections', 'Convert existing relations explicitly before changing their target collection.'));
                }
                $defaults = $settings['default'] ?? [];
                foreach (is_array($defaults) ? $defaults : [$defaults] as $value) {
                    if ($value !== '' && $this->items->findForUpdate((int) $value)?->collection_id !== $target) {
                        throw new CollectionSchemaException(d__('collections', 'Relation defaults must reference existing items of the target collection.'));
                    }
                }
            }
            if (
                $current !== null && $current->type === FieldType::Select && $dto->type === $current->type && ! $this->context->isActive()
                && ($settings['options'] ?? []) !== ($current->settings['options'] ?? []) && $this->repository->hasSelectValuesOutside($current->id, array_column($settings['options'] ?? [], 'code'))
            ) {
                throw new CollectionSchemaException(d__('collections', 'A used option cannot be removed. Disable it instead.'));
            }
            $attributes = [
                'collection_id' => $dto->collectionId, 'code' => $dto->code, 'name' => $current?->owner_module !== null ? $current->getRawOriginal('name') : $dto->name,
                'type' => $dto->type->value, 'required' => $dto->required, 'multiple' => $dto->multiple,
                'in_list' => $dto->inList, 'sort' => $dto->sort, 'role' => $dto->role,
                'settings' => $settings,
            ];
            if ($owned !== null) {
                $attributes['owner_module'] = $collection->owner_module;
                $attributes['name'] = $owned->label->message;
                $attributes['label_domain'] = $owned->label->domain;
            }
            if ($current !== null) {
                $this->repository->update($id, $attributes);
            } else {
                $id = $this->repository->create($attributes)->id;
            }
            $this->changes->dispatch(new CollectionChangeDTO('field', 'saved', $dto->collectionId, $id, $before, new CollectionChangeStateDTO(null, null, $dto->code)));
            return $id;
        });
    }
}
