<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use Johncms\Auth\Identity;

/**
 * Fills in what a visitor is allowed, once the authenticators have decided who they are.
 *
 * Deliberately separate from authentication: how somebody proved their identity — a cookie, an
 * API token, later an external service — has nothing to do with what that identity may do, and
 * keeping the two apart is what lets a new way of signing in arrive without touching this.
 *
 * A guest goes through here too. Anonymous visitors have a role of their own, and it is what
 * decides whether the forum, the library and the rest are open without signing in.
 */
final readonly class PermissionResolver
{
    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    public function resolve(Identity $identity, ?int $now = null): Identity
    {
        $roles = $identity->isGuest()
            ? array_filter([$this->roles->guestRole()])
            : $this->roles->forUser($identity->userId, $now ?? time())->all();

        $slugs = [];
        $ids = [];

        foreach ($roles as $role) {
            $slugs[] = $role->slug;
            $ids[] = $role->id;
        }

        return $identity->withGrants($slugs, $this->roles->permissionsFor($ids));
    }
}
