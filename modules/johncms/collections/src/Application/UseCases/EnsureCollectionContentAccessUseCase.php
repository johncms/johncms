<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class EnsureCollectionContentAccessUseCase
{
    /** @param iterable<\Johncms\Modules\Collections\Application\Extension\CollectionContentAccessProviderInterface> $owners */
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
