<?php

declare(strict_types=1);

namespace Johncms\Online;

use Johncms\Users\User;

class Tabs
{
    protected ?User $user;

    public function __construct(?User $user)
    {
        $this->user = $user;
    }

    public function getTabs(): array
    {
        $route = di('route');
        $tabs = [
            'users'   => [
                'name'   => __('Users'),
                'url'    => route('online.index'),
                'active' => ($route->getName() === 'online.index'),
            ],
            'history' => [
                'name'   => __('History'),
                'url'    => route('online.history'),
                'active' => ($route->getName() === 'online.history'),
            ],
        ];

        if ($this->user?->hasAnyRole()) {
            $tabs['guest'] = [
                'name'   => __('Guests'),
                'url'    => route('online.guests'),
                'active' => ($route->getName() === 'online.guests'),
            ];
            $tabs['ip'] = [
                'name'   => __('IP Activity'),
                'url'    => route('online.ipActivity'),
                'active' => ($route->getName() === 'online.ipActivity'),
            ];
        }

        return $tabs;
    }
}
