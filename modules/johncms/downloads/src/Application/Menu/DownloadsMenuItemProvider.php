<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Menu;

use Johncms\Cache\CacheInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The downloads in the main menu, with the files of the last days. The count is the same for every
 * visitor, so it is kept for a while under the tag of the counters.
 */
final readonly class DownloadsMenuItemProvider implements MenuItemProviderInterface
{
    public function __construct(
        private DownloadFileRepositoryInterface $files,
        private CacheInterface $cache,
    ) {
    }

    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('downloads', 'Downloads'),
            '/downloads/',
            icon: 'download',
            permission: DownloadsPermissions::VIEW,
            weight: 40,
            counter: fn (): int => $this->cache->remember(
                'downloads_menu_new_files',
                600,
                fn (): int => $this->files->countNewFiles(),
                ['counters']
            ),
        );
    }
}
