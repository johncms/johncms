<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Menu;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The way into the panel, in the part of the main menu kept for the staff.
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
    }
}
