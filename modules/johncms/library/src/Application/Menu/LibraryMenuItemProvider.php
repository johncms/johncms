<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Menu;

use Johncms\Cache\CacheInterface;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The library in the main menu, with the articles of the last days. The count is the same for
 * every visitor, so it is kept for a while under the tag of the counters.
 */
final readonly class LibraryMenuItemProvider implements MenuItemProviderInterface
{
    public function __construct(
        private LibraryTextRepositoryInterface $texts,
        private CacheInterface $cache,
    ) {
    }

    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('library', 'Library'),
            '/library/',
            icon: 'text',
            permission: LibraryPermissions::VIEW,
            weight: 50,
            counter: fn (): int => $this->cache->remember(
                'library_menu_new_texts',
                3200,
                fn (): int => $this->texts->countNew(),
                ['counters']
            ),
        );
    }
}
