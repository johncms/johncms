<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Menu;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Cache\CacheInterface;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The albums in the main menu, with the photos of the last days: the public ones for everybody,
 * also those behind a password for a moderator, who has to look at them. A guest gets no item:
 * every page of the module answers a guest with a 404.
 */
final readonly class AlbumMenuItemProvider implements MenuItemProviderInterface
{
    private const int RECENT = 259200;

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private AlbumPhotoRepositoryInterface $photos,
        private CacheInterface $cache,
    ) {
    }

    public function menuItems(): iterable
    {
        if (! $this->currentUser->isValid()) {
            return;
        }

        yield new MenuItem(
            MenuArea::Main,
            d__('album', 'Photo Albums'),
            '/album/',
            icon: 'picasa',
            weight: 70,
            counter: function (): int {
                [$public, $shared] = $this->cache->remember(
                    'album_menu_new_photos',
                    600,
                    fn (): array => [
                        $this->photos->countNewPublicSince(time() - self::RECENT),
                        $this->photos->countNewSharedSince(time() - self::RECENT),
                    ],
                    ['counters']
                );

                return $shared > 0 && $this->accessChecker->allows(AlbumPermissions::MODERATE) ? $shared : $public;
            },
        );
    }
}
