<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Cache;

class UserPermissionChecker
{
    private ?array $userPermissions = null;
    private string $cacheId;
    private Cache $cache;

    public function __construct(private User $user)
    {
        $this->cacheId = 'user_permissions_' . $this->user->id;
        $this->cache = di(Cache::class);
    }

    public function getUserPermissions()
    {
        if ($this->userPermissions !== null) {
            return $this->userPermissions;
        }

        $this->userPermissions = $this->cache->remember($this->cacheId, 60, function () {
            $permissions = [];
            /** @var Role[] $roles */
            $roles = $this->user->roles()->get();
            foreach ($roles as $role) {
                $permissions = array_merge($permissions, $role->permissions()->get()->pluck('name')->toArray());
            }
            return $permissions;
        });

        return $this->userPermissions;
    }

    public function hasPermission(array|string $permissions): bool
    {
        $userPermissions = $this->getUserPermissions();
        if (! is_array($permissions)) {
            $permissions = [$permissions];
        }
        $intersectedRoles = array_intersect($permissions, $userPermissions);

        return ! empty($intersectedRoles);
    }

    public function clearCache()
    {
        $this->cache->forget($this->cacheId);
        $this->userPermissions = null;
    }
}
