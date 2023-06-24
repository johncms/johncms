<?php

declare(strict_types=1);

namespace Johncms\Content\Providers;

use Johncms\AbstractServiceProvider;
use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;
use Johncms\View\Menu\MenuItem;

final class MenuServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {

        MenuFactory::create(Menu::ADMIN_SIDEBAR)
            ->add(
                new MenuItem(
                    code: 'content',
                    url:  route('content.admin.index'),
                    name: __('Content'),
                    icon: 'folder',
                    sort: 110,
                )
            );
    }
}
