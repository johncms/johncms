<?php

declare(strict_types=1);

namespace Johncms\News\Providers;

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
