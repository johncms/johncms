<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\Updates\SystemUpdateStateInterface;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

final readonly class CollectionContentMenuProvider implements MenuItemProviderInterface
{
    public function __construct(
        private ContentCollectionRepositoryInterface $collections,
        private EnsureCollectionContentAccessUseCase $access,
        private SystemUpdateStateInterface $update,
    ) {
    }

    public function menuItems(): iterable
    {
        // Runs on every page: until an update finishes the schema may not have what the query reads.
        if ($this->update->isRequired() || $this->access->canManageSchema()) {
            return;
        }
        foreach ($this->collections->getForContentMenu() as $collection) {
            if ($this->access->allows($collection)) {
                yield new MenuItem(MenuArea::Admin, $collection->name, CollectionWorkspaceMapper::url($collection->id), 'layout-grid');
            }
        }
    }
}
