<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth;

use Johncms\Auth\Authorization\PermissionMatcher;

/**
 * Who is making the current request, as a value object.
 *
 * Deliberately free of the database and of HTTP: it carries identifiers, role slugs and
 * permission keys, nothing that needs a query to exist. That is what lets a test build the
 * visitor it needs in one line instead of booting half the application, and what keeps the
 * authorization rules testable on their own. The Eloquent user behind the id is loaded by
 * whoever actually needs the profile fields, not by this object.
 *
 * Immutable: an identity belongs to one request and is never edited in place.
 */
final readonly class Identity
{
    /**
     * @param int                 $userId         0 for a visitor who is not signed in.
     * @param list<string>        $roles          Role slugs, including the implicit default ones.
     * @param list<string>        $permissions    Permission keys and patterns granted by the roles.
     * @param int|null            $impersonatorId The administrator browsing as this user, if any.
     * @param list<string>|null   $tokenAbilities Restrictions of the API token the request came
     *                                            with; null when the request is not token-based.
     *                                            An empty list is a token that may do nothing.
     * @param int|null            $sessionId      The session row this request arrived on. Needed
     *                                            to close exactly this session on sign-out, to
     *                                            mark it as "this device" in the list, and to
     *                                            find the way back out of impersonation.
     */
    public function __construct(
        public int $userId = 0,
        public array $roles = [],
        public array $permissions = [],
        public AuthMethod $method = AuthMethod::Guest,
        public ?int $impersonatorId = null,
        public ?array $tokenAbilities = null,
        public ?int $sessionId = null,
    ) {
    }

    public static function guest(): self
    {
        return new self();
    }

    /**
     * The same visitor with their roles and permissions filled in.
     *
     * Authenticators answer who the visitor is; what they are allowed is looked up separately,
     * for guests as much as for anybody else — a guest without the permissions of the guest role
     * would be barred from the parts of the site that are open to everyone.
     *
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    public function withGrants(array $roles, array $permissions): self
    {
        return new self(
            userId: $this->userId,
            roles: $roles,
            permissions: $permissions,
            method: $this->method,
            impersonatorId: $this->impersonatorId,
            tokenAbilities: $this->tokenAbilities,
            sessionId: $this->sessionId,
        );
    }

    public function isGuest(): bool
    {
        return $this->userId === 0;
    }

    public function isImpersonating(): bool
    {
        return $this->impersonatorId !== null;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    /**
     * Whether the roles of this identity grant the permission.
     *
     * This is the raw grant, not the final answer: bans, impersonation limits and token
     * abilities can still deny it. Ask AccessCheckerInterface unless you specifically need
     * what the roles say.
     */
    public function hasPermission(string $permission): bool
    {
        return PermissionMatcher::matchesAny($this->permissions, $permission);
    }

    /**
     * Whether the token the request came with allows the permission. True when there is no
     * token: a cookie request is limited by the roles alone.
     */
    public function tokenAllows(string $permission): bool
    {
        if ($this->tokenAbilities === null) {
            return true;
        }

        return PermissionMatcher::matchesAny($this->tokenAbilities, $permission);
    }
}
