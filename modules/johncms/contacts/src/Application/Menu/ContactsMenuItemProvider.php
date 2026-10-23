<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Menu;

use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

final readonly class ContactsMenuItemProvider implements MenuItemProviderInterface
{
    public function menuItems(): iterable
    {
        yield new MenuItem(MenuArea::Main, d__('contacts', 'Contacts'), '/contacts/', icon: 'messages', weight: 90);
    }
}
