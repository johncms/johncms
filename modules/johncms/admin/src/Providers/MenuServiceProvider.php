<?php

declare(strict_types=1);

namespace Johncms\Admin\Providers;

use Johncms\AbstractServiceProvider;
use Johncms\Users\User;
use Johncms\View\Menu\Menu;
use Johncms\View\Menu\MenuFactory;
use Johncms\View\Menu\MenuItem;

final class MenuServiceProvider extends AbstractServiceProvider
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
                    sort: 100,
                )
            )
            ->add(
                new MenuItem(
                    code: 'system',
                    url:  '/admin/system',
                    name: __('System'),
                    icon: 'settings',
                    sort: 200,
                )
            )
            ->addChildren(
                'system',
                new MenuItem(
                    code: 'modules',
                    url:  route('admin.modules'),
                    name: __('Modules'),
                    icon: 'shopping-cart',
                    sort: 100,
                )
            )
            ->addChildren(
                'system',
                new MenuItem(
                    code: 'systemCheck',
                    url:  route('admin.system.check'),
                    name: __('System Check'),
                    icon: 'check-circle',
                    sort: 200,
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
