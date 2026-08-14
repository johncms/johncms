<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\AuthMethod;
use Johncms\Auth\Identity;

/**
 * Builds the visitor a test needs, in one line and without a database.
 *
 * This is the reason Identity is a plain value object: authorization is testable without a
 * container, a schema or a request. If a change ever makes one of these helpers need a query,
 * that is the signal that Identity has grown a dependency it should not have.
 */
final class IdentityFactory
{
    public static function guest(): Identity
    {
        return Identity::guest();
    }

    /**
     * A signed-in visitor with no permissions beyond what the test grants.
     *
     * @param list<string> $permissions
     * @param list<string> $roles
     */
    public static function user(int $id = 1, array $permissions = [], array $roles = ['user']): Identity
    {
        return new Identity(
            userId: $id,
            roles: $roles,
            permissions: $permissions,
            method: AuthMethod::Session,
        );
    }

    /**
     * @param list<string> $permissions
     */
    public static function withPermissions(array $permissions, int $id = 1): Identity
    {
        return self::user($id, $permissions);
    }

    /**
     * @param list<string> $roles
     */
    public static function withRoles(array $roles, int $id = 1): Identity
    {
        return self::user($id, roles: $roles);
    }

    public static function superAdmin(int $id = 1): Identity
    {
        return self::user($id, ['*'], ['supervisor']);
    }

    /**
     * A request authenticated by an API token: the roles grant one thing, the token may allow
     * less. Used to check that abilities cut down permissions rather than add to them.
     *
     * @param list<string> $permissions
     * @param list<string> $abilities
     */
    public static function token(array $permissions, array $abilities, int $id = 1): Identity
    {
        return new Identity(
            userId: $id,
            roles: ['user'],
            permissions: $permissions,
            method: AuthMethod::Token,
            tokenAbilities: $abilities,
        );
    }

    /**
     * @param list<string> $permissions
     */
    public static function impersonated(int $id = 1, int $impersonatorId = 2, array $permissions = []): Identity
    {
        return new Identity(
            userId: $id,
            roles: ['user'],
            permissions: $permissions,
            method: AuthMethod::Session,
            impersonatorId: $impersonatorId,
        );
    }
}
