<?php

declare(strict_types=1);

namespace Johncms\Library\Providers;

use Johncms\ServiceProvider;
use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;
use Johncms\View\Menu\MenuItem;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        MenuFactory::create(Menu::PUBLIC_SIDEBAR)
            ->add(
                new MenuItem(
                    code: 'library',
                    url:  '/library/',
                    name: __('Library'),
                    icon: 'text',
                    sort: 50
                )
            );
    }
}
