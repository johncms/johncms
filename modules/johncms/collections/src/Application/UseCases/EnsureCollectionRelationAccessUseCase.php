<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

/** User-facing writes check the current visitor; trusted module writes use the structural API. */
final readonly class EnsureCollectionRelationAccessUseCase
{
    public function __construct(private ContentCollectionFieldRepositoryInterface $fields, private EnsureCollectionContentAccessUseCase $access)
    {
    }

    /** @param array<string, mixed> $values */
    public function execute(int $collectionId, array $values, bool $applyDefaults = false): void
    {
        foreach ($this->fields->getForUpdate($collectionId) as $field) {
            if ($field->type !== FieldType::Relation) {
                continue;
            }
            $value = array_key_exists($field->code, $values) ? $values[$field->code] : ($applyDefaults ? ($field->settings['default'] ?? null) : null);
            $submitted = is_array($value) ? $value : [$value];
            if (array_any($submitted, static fn ($entry): bool => is_string($entry) && trim($entry) !== '') && is_int($field->settings['target_collection_id'] ?? null)) {
                $this->access->execute($field->settings['target_collection_id']);
            }
        }
    }
}
