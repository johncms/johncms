<?php

declare(strict_types=1);

namespace Johncms\Modules\Help\Application\Menu;

use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

final readonly class HelpMenuItemProvider implements MenuItemProviderInterface
{
    public function menuItems(): iterable
    {
        yield new MenuItem(MenuArea::Main, d__('help', 'Information, FAQ'), '/help/', icon: 'info', weight: 80);
    }
}
