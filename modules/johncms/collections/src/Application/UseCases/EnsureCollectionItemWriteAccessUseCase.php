<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class EnsureCollectionItemWriteAccessUseCase
{
    public function __construct(private EnsureCollectionContentAccessUseCase $access, private ContentCollectionItemRepositoryInterface $items)
    {
    }

    /** Called under the source collection lock; missing patch attributes preserve their values.
     * @param array<string, mixed> $attributes
     */
    public function execute(int $collectionId, ?int $id, array $attributes): void
    {
        $this->access->executeAction($collectionId, $id === null ? 'create' : 'edit');
        $current = $id !== null ? $this->items->findForUpdate($id) : null;
        if ($id !== null && ($current === null || $current->collection_id !== $collectionId)) {
            throw new CollectionAccessDeniedException('The item does not belong to the authorized collection.');
        }
        $active = $current !== null ? $current->active : false;
        $publicationChanged = array_key_exists('active', $attributes) && $attributes['active'] !== $active;
        foreach (['activeFrom' => 'active_from', 'activeTo' => 'active_to'] as $property => $column) {
            if (array_key_exists($property, $attributes)) {
                $old = $current?->getRawOriginal($column);
                $publicationChanged = $publicationChanged || $this->date($attributes[$property]) !== $this->date($old);
            }
        }
        if ($current !== null && ($active || ($attributes['active'] ?? false)) && array_key_exists('sectionId', $attributes) && $attributes['sectionId'] !== $current->section_id) {
            $publicationChanged = true;
        }
        if ($publicationChanged) {
            $this->access->executeAction($collectionId, 'publish');
        }
    }

    private function date(mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace('T', ' ', $value);
            return strlen($value) === 16 ? $value . ':00' : $value;
        }
        return $value;
    }
}
