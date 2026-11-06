<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use InvalidArgumentException;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Extension\CollectionContentAccessProviderInterface;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class EnsureCollectionContentAccessUseCase
{
    /** @param iterable<CollectionContentAccessProviderInterface> $owners */
    public function __construct(private AccessCheckerInterface $access, private ContentCollectionRepositoryInterface $collections, private iterable $owners = [])
    {
    }

    public function canManageSchema(): bool
    {
        return $this->access->allows(CollectionsPermissions::MANAGE);
    }

    public function allows(ContentCollection $collection): bool
    {
        if ($this->canManageSchema()) {
            return true;
        }
        $permission = $collection->settings['_content_permission'] ?? null;
        return is_string($permission) && $permission !== '' && $this->access->allows($permission, $collection->id);
    }

    /** @return array<string, string> */
    public static function actions(): array
    {
        return [
            'create' => d__('collections', 'Create items'), 'edit' => d__('collections', 'Edit items'),
            'publish' => d__('collections', 'Publish items'), 'delete' => d__('collections', 'Delete items'),
            'sections' => d__('collections', 'Manage sections'),
        ];
    }

    public function allowsAction(ContentCollection $collection, string $action): bool
    {
        if (! array_key_exists($action, self::actions())) {
            throw new InvalidArgumentException('Unknown collection action.');
        }
        if ($this->canManageSchema()) {
            return true;
        }
        $permission = $collection->settings['content_actions'][$action] ?? null;
        return $this->allows($collection) && ($permission === null || $this->access->allows($permission, $collection->id));
    }

    public function executeAction(int $collectionId, string $action): void
    {
        $this->execute($collectionId);
        $collection = $this->collections->findForUpdate($collectionId);
        if ($collection === null || ! $this->allowsAction($collection, $action)) {
            throw new CollectionAccessDeniedException('This collection action is not permitted.');
        }
    }

    public function execute(int $collectionId): void
    {
        foreach ($this->owners as $owner) {
            $owner->assertAvailable($collectionId);
        }
        if ($this->canManageSchema()) {
            return;
        }
        $collection = $this->collections->findById($collectionId);
        if ($collection === null || ! $this->allows($collection)) {
            throw new CollectionAccessDeniedException('Content access to this collection is required.');
        }
    }
}
