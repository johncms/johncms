<?php

declare(strict_types=1);

namespace Johncms\Admin\Providers;

use Johncms\ServiceProvider;
use Johncms\Users\User;
use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;
use Johncms\View\Menu\MenuItem;

class MenuServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $user = $this->container->get(User::class);

        MenuFactory::create(Menu::ADMIN_SIDEBAR)
            ->add(
                new MenuItem(
                    code: 'users',
                    url:  route('admin.users'),
                    name: __('Users'),
                    icon: 'users',
                )
            );

        if ($user?->isAdmin()) {
            MenuFactory::create(Menu::PUBLIC_ADMIN)
                ->add(
                    new MenuItem(
                        code: 'adminPanel',
                        url:  '/admin/',
                        name: __('Admin Panel'),
                        icon: 'settings'
                    )
                );
        }
    }
}
