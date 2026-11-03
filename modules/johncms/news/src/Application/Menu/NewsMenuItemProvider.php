<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Menu;

use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsCollectionDeclaration;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

final readonly class NewsMenuItemProvider implements MenuItemProviderInterface
{
    public function __construct(private NewsReadServiceInterface $news, private CollectionLookupApiInterface $lookup, private NewsCollectionAvailability $availability)
    {
    }

    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('news', 'News archive'),
            '/news/',
            icon: 'book',
            weight: 10,
            counter: function (): int {
                $days = (int) (config('news')['homepage_days'] ?? 0);
                return $days > 0 && $this->availability->ready() ? $this->news->countNew($days) : 0;
            }
        );
        $collection = $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY);
        if ($collection === null) {
            return;
        }
        $workspace = '/admin/collections/' . $collection->id . '/items';
        yield new MenuItem(MenuArea::Admin, d__('news', 'News'), $workspace, icon: 'newspaper', permission: NewsPermissions::MANAGE, weight: 10);
        yield new MenuItem(MenuArea::AdminActions, d__('news', 'Write the news'), $workspace . '/new', icon: 'pencil', permission: NewsPermissions::MANAGE, weight: 30);
    }
}
