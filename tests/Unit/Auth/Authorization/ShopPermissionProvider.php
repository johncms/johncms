<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionProviderInterface;

/**
 * A provider of some other module, used to check that purging one module leaves the rest alone.
 * It lives in this namespace deliberately — the module under test claims a different one.
 */
final class ShopPermissionProvider implements PermissionProviderInterface
{
    public function permissions(): iterable
    {
        return [new PermissionDefinition('shop.manage', 'shop', 'Manage the shop')];
    }
}
