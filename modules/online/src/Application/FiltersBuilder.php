<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application;

use Johncms\Users\User;

final readonly class FiltersBuilder
{
    public function __construct(private User $currentUser)
    {
    }

    public function build(string $active): array
    {
        $filters = [
            'users'   => ['name' => __('Users'),   'url' => '/online/',         'active' => $active === 'users'],
            'history' => ['name' => __('History'), 'url' => '/online/history/', 'active' => $active === 'history'],
        ];

        if ($this->currentUser->rights) {
            $filters['guest'] = ['name' => __('Guests'),      'url' => '/online/guest/', 'active' => $active === 'guest'];
            $filters['ip']    = ['name' => __('IP Activity'), 'url' => '/online/ip/',    'active' => $active === 'ip'];
        }

        return $filters;
    }
}
