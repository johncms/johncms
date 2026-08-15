<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\User;

final readonly class FiltersBuilder
{
    public function __construct(private AccessCheckerInterface $accessChecker)
    {
    }

    public function build(string $active): array
    {
        $filters = [
            'users'   => ['name' => __('Users'),   'url' => '/online/',         'active' => $active === 'users'],
            'history' => ['name' => __('History'), 'url' => '/online/history/', 'active' => $active === 'history'],
        ];

        if ($this->accessChecker->allows(CorePermissions::USERS_ORIGIN_VIEW)) {
            $filters['guest'] = ['name' => __('Guests'),      'url' => '/online/guest/', 'active' => $active === 'guest'];
            $filters['ip']    = ['name' => __('IP Activity'), 'url' => '/online/ip/',    'active' => $active === 'ip'];
        }

        return $filters;
    }
}
