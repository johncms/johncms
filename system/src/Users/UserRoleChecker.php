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

class UserRoleChecker
{
    private ?array $userRoles = null;
    private string $cacheId;
    private Cache $cache;

    public function __construct(private User $user)
    {
        $this->cacheId = 'user_roles_' . $this->user->id;
        $this->cache = di(Cache::class);
    }

    public function getUserRoles()
    {
        if ($this->userRoles !== null) {
            return $this->userRoles;
        }

        $this->userRoles = $this->cache->remember($this->cacheId, 60, fn() => $this->user->roles()->get()->toArray());

        return $this->userRoles;
    }

    public function hasRole(array|string $roles): bool
    {
        $userRoles = $this->getUserRoles();
        $roleNames = array_column($userRoles, 'name');

        if (! is_array($roles)) {
            $roles = [$roles];
        }

        $intersectedRoles = array_intersect($roles, $roleNames);

        return ! empty($intersectedRoles);
    }

    public function hasAnyRole(): bool
    {
        return ! empty($this->getUserRoles());
    }

    public function clearCache()
    {
        $this->cache->forget($this->cacheId);
        $this->userRoles = null;
    }
}
