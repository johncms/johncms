<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\DTO\CollectionDeclarationDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldDeclarationDTO;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Updates\DeclarationSynchronizerInterface;

/** Safe structural additions precede owner hooks; validation follows them. */
final readonly class CollectionDeclarationsSynchronizer implements DeclarationSynchronizerInterface
{
    public function __construct(
        private CollectionDeclarationRegistry $registry,
        private ContentCollectionRepositoryInterface $collections,
        private ContentCollectionFieldRepositoryInterface $fields,
        private CollectionTransactionInterface $transaction,
        private CollectionFieldSettingsValidator $settingsValidator = new CollectionFieldSettingsValidator(),
        private ?CollectionFileValues $fileValues = null,
    ) {
    }

    public function owner(): string
    {
        // Like role synchronization, run for installations/enabling of any loaded consumer.
        return 'system';
    }

    public function synchronize(): void
    {
        foreach ($this->registry->all() as ['owner' => $owner, 'declaration' => $declaration]) {
            $this->transaction->run(function () use ($owner, $declaration): void {
                $collection = $this->collections->findByDeclaration($owner, $declaration->key);
                $collision = $this->collections->findByCode($declaration->code);
                if ($collection === null) {
                    if ($collision !== null) {
                        return;
                    }
                    $collection = $this->collections->create([
                        'owner_module' => $owner, 'declaration_key' => $declaration->key,
                        'code' => $declaration->code, 'name' => $declaration->label->message,
                        'label_domain' => $declaration->label->domain, 'active' => true, 'public' => false,
                        'settings' => $this->settings($declaration, []),
                    ]);
                }
                $collection = $this->collections->findForUpdate($collection->id);
                if ($collection === null) {
                    throw new \RuntimeException('The collection changed during synchronization. Rerun system:update.');
                }
                $attributes = [
                    'name' => $declaration->label->message, 'label_domain' => $declaration->label->domain,
                    'settings' => $this->settings($declaration, $collection->settings ?? []),
                ];
                if ($collision === null || $collision->id === $collection->id) {
                    $attributes['code'] = $declaration->code;
                }
                $this->collections->update($collection->id, $attributes);
                $byCode = $this->fields->getForUpdate($collection->id)->keyBy('code');
                foreach ($declaration->fields as $declared) {
                    $this->settingsValidator->validate($declared->type, $declared->multiple, $declared->settings, $declared->required);
                    $field = $byCode[$declared->code] ?? null;
                    if ($field !== null && $field->owner_module !== $owner) {
                        continue;
                    }
                    if ($field === null && $declared->renamedFrom !== null) {
                        $source = $byCode[$declared->renamedFrom] ?? null;
                        if ($source !== null && $source->owner_module === $owner) {
                            $this->fields->update($source->id, ['code' => $declared->code]);
                            $field = $source;
                            unset($byCode[$declared->renamedFrom]);
                        }
                    }
                    $roleHolder = $declared->role !== null ? $this->fields->findByRole($collection->id, $declared->role) : null;
                    if ($roleHolder !== null && $roleHolder->id !== $field?->id) {
                        continue;
                    }
                    $fieldAttributes = $this->fieldAttributes($declared, $owner);
                    if ($field === null) {
                        $field = $this->fields->create($fieldAttributes + ['collection_id' => $collection->id]);
                    } else {
                        // A type or multiplicity conversion belongs to the trusted owner hook.
                        if ($field->type !== $declared->type || $field->multiple !== $declared->multiple) {
                            unset($fieldAttributes['type'], $fieldAttributes['multiple']);
                        }
                        $fieldAttributes['settings'] = array_replace($field->settings ?? [], $declared->settings);
                        if ($field->type === \Johncms\Modules\Collections\Domain\Enums\FieldType::Select && $this->fields->hasSelectValuesOutside($field->id, array_column($fieldAttributes['settings']['options'] ?? [], 'code'))) {
                            $fieldAttributes['settings']['options'] = $field->settings['options'] ?? [];
                        }
                        if (
                            $field->type === \Johncms\Modules\Collections\Domain\Enums\FieldType::Relation
                            && ($fieldAttributes['settings']['target_collection_id'] ?? null) !== ($field->settings['target_collection_id'] ?? null) && $this->fields->hasValues($field->id)
                        ) {
                            $fieldAttributes['settings']['target_collection_id'] = $field->settings['target_collection_id'] ?? null;
                        }
                        if (
                            $field->type === \Johncms\Modules\Collections\Domain\Enums\FieldType::File
                            && ($fieldAttributes['settings']['file_storage'] ?? 'legacy') !== ($field->settings['file_storage'] ?? 'legacy') && $this->fields->hasValues($field->id)
                        ) {
                            $fieldAttributes['settings']['file_storage'] = $field->settings['file_storage'] ?? 'legacy';
                        }
                        $this->fields->update($field->id, $fieldAttributes);
                    }
                    $byCode[$declared->code] = $field;
                    if ($declared->type === \Johncms\Modules\Collections\Domain\Enums\FieldType::File && ($this->fields->findForUpdate($field->id)?->settings['file_storage'] ?? 'legacy') === 'registry') {
                        if ($this->fileValues === null) {
                            throw new \LogicException('Collection file values are not configured.');
                        }
                        $this->fileValues->withinTransaction(new \Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO('field', 'saved', $collection->id, $field->id, null, null));
                    }
                }
            });
        }
    }

    public function validate(): void
    {
        $problems = $this->problems();
        if ($problems !== []) {
            throw new \RuntimeException(implode("\n", $problems));
        }
    }

    /** @return list<string> */
    public function problems(): array
    {
        $problems = [];
        foreach ($this->registry->all() as ['owner' => $owner, 'declaration' => $declaration]) {
            $identity = $owner . '/' . $declaration->key;
            $collection = $this->collections->findByDeclaration($owner, $declaration->key);
            if ($collection === null) {
                $problems[] = sprintf(d__('collections', 'Collection %s is missing. Run system:update; rename any custom collection using code %s.'), $identity, $declaration->code);
                continue;
            }
            if (
                $collection->code !== $declaration->code || $collection->getRawOriginal('name') !== $declaration->label->message
                || $collection->label_domain !== $declaration->label->domain || ($collection->settings['_content_permission'] ?? null) !== $declaration->contentPermission
            ) {
                $problems[] = sprintf(d__('collections', 'Collection %s differs from its declaration. Run system:update.'), $identity);
            }
            $byCode = $this->fields->getByCollection($collection->id)->keyBy('code');
            foreach ($declaration->fields as $declared) {
                $field = $byCode[$declared->code] ?? null;
                if ($field === null) {
                    $problems[] = sprintf(d__('collections', 'Field %s in %s is missing. Run system:update and resolve any conflicting field role.'), $declared->code, $identity);
                } elseif ($field->owner_module !== $owner) {
                    $problems[] = sprintf(d__('collections', 'Field %s in %s belongs to the administrator. Rename this custom field before updating.'), $declared->code, $identity);
                } elseif ($field->type !== $declared->type || $field->multiple !== $declared->multiple) {
                    $problems[] = sprintf(d__('collections', 'Field %s in %s needs type or multiplicity conversion in the owner update hook.'), $declared->code, $identity);
                } elseif ($field->type === \Johncms\Modules\Collections\Domain\Enums\FieldType::Select && $this->fields->hasSelectValuesOutside($field->id, array_column($declared->settings['options'] ?? [], 'code'))) {
                    $problems[] = sprintf(d__('collections', 'Field %s in %s needs option migration in the owner update hook.'), $declared->code, $identity);
                } elseif (! $this->matches($field, $declared)) {
                    $problems[] = sprintf(d__('collections', 'Field %s in %s differs from its declaration. Run system:update.'), $declared->code, $identity);
                }
            }
        }
        return $problems;
    }

    /** @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function settings(CollectionDeclarationDTO $declaration, array $existing): array
    {
        return array_replace($declaration->settings, $existing, ['_content_permission' => $declaration->contentPermission]);
    }

    /** @return array<string, mixed> */
    private function fieldAttributes(CollectionFieldDeclarationDTO $field, string $owner): array
    {
        return [
            'owner_module' => $owner, 'code' => $field->code,
            'name' => $field->label->message, 'label_domain' => $field->label->domain,
            'type' => $field->type->value, 'multiple' => $field->multiple, 'required' => $field->required,
            'role' => $field->role, 'in_list' => $field->inList, 'sort' => $field->sort, 'settings' => $field->settings,
        ];
    }

    private function matches(ContentCollectionField $field, CollectionFieldDeclarationDTO $declared): bool
    {
        foreach ($declared->settings as $key => $value) {
            if (json_encode($field->settings[$key] ?? null) !== json_encode($value)) {
                return false;
            }
        }
        return $field->getRawOriginal('name') === $declared->label->message && $field->label_domain === $declared->label->domain
            && $field->required === $declared->required && $field->role === $declared->role
            && $field->in_list === $declared->inList && $field->sort === $declared->sort;
    }
}
