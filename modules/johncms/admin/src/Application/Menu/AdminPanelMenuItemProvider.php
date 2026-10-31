<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Menu;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The way into the panel, in the part of the main menu kept for the staff, and the quick actions of
 * the panel itself on its dashboard.
 */
final readonly class AdminPanelMenuItemProvider implements MenuItemProviderInterface
{
    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Staff,
            d__('admin', 'Admin Panel'),
            '/admin/',
            icon: 'settings',
            permission: CorePermissions::ADMIN_ACCESS,
            weight: 10,
        );

        yield new MenuItem(MenuArea::AdminActions, d__('admin', 'Find a user'), '/admin/users', icon: 'search', permission: CorePermissions::ADMIN_ACCESS, weight: 10);
        yield new MenuItem(MenuArea::AdminActions, d__('admin', 'Search by IP'), '/admin/ip-search', icon: 'network', permission: CorePermissions::ADMIN_ACCESS, weight: 20);
        yield new MenuItem(MenuArea::AdminActions, d__('admin', 'Add an advertisement'), '/admin/ads/new', icon: 'megaphone', permission: CorePermissions::ADMIN_ACCESS, weight: 60);
        yield new MenuItem(MenuArea::AdminActions, d__('admin', 'Site settings'), '/admin/settings', icon: 'settings', permission: CorePermissions::ADMIN_SETTINGS_MANAGE, weight: 90);
    }
}
