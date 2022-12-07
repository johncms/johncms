<?php

declare(strict_types=1);

namespace Johncms\News\Providers;

use Johncms\AbstractServiceProvider;
use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;
use Johncms\View\Menu\MenuItem;

final class MenuServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        MenuFactory::create(Menu::PUBLIC_SIDEBAR)
            ->add(
                new MenuItem(
                    code:    'news',
                    url:     route('news.section'),
                    name:    __('News'),
                    icon:    'book',
                    counter: ['counters', 'news'],
                    sort:    10
                )
            );
    }
}
