<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RolePermission;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Users\User;
use RuntimeException;

/**
 * Accounts for the functional suite, written to the database a test drives requests against.
 *
 * A test used to look for an account the local stand happened to have, and skip itself when it
 * had none; it now says which permissions the visitor holds and gets exactly that. The password
 * of every account is self::PASSWORD, for the tests that sign in through the form.
 *
 * `users` is a legacy table whose model would drop the columns missing from its fillable list, so
 * the row is written with Fixture, which also fills in the required columns nobody asked about.
 */
final class FunctionalUserFactory
{
    public const string PASSWORD = 'functional-test-password';

    private static int $sequence = 0;

    /**
     * @param array<string, mixed> $attributes
     * @param list<string>         $roles Slugs of roles that already exist, system ones included.
     */
    public static function create(array $attributes = [], array $roles = []): User
    {
        $number = ++self::$sequence;

        $attributes = array_merge(
            [
                'name'     => 'Tester' . $number,
                'password' => password_hash(self::PASSWORD, PASSWORD_DEFAULT),
                'preg'     => 1,
                'email_confirmed' => 1,
                'datereg'  => time(),
                'lastdate' => time(),
            ],
            $attributes
        );

        $id = Fixture::insert('users', $attributes);

        foreach ($roles as $slug) {
            self::grant($id, $slug);
        }

        return User::query()->findOrFail($id);
    }

    /**
     * An account holding exactly the named permissions and nothing else.
     *
     * The role is created for the account, so a test states what the visitor may do instead of
     * borrowing a role whose permissions someone else may change.
     *
     * @param list<string>         $permissions
     * @param array<string, mixed> $attributes
     */
    public static function createWithPermissions(array $permissions, array $attributes = [], int $level = 10): User
    {
        $role = self::createRole($permissions, $level);
        $user = self::create($attributes);

        self::grantRole($user->id, $role->id);

        return $user;
    }

    /**
     * An account of the staff: every permission there is, by standing high enough that none is
     * asked for. What a supervisor of a real site holds.
     */
    public static function createSupervisor(array $attributes = []): User
    {
        return self::create($attributes, [SystemRole::Supervisor->value]);
    }

    /**
     * @param list<string> $permissions
     */
    public static function createRole(array $permissions, int $level = 10, ?string $slug = null): Role
    {
        $slug ??= 'functional-test-role-' . ++self::$sequence;

        /** @var Role $role */
        $role = Role::query()->create(
            [
                'slug'       => $slug,
                'name'       => $slug,
                'level'      => $level,
                'is_system'  => false,
                'is_default' => false,
                'is_guest'   => false,
                'created_at' => time(),
                'updated_at' => time(),
            ]
        );

        foreach ($permissions as $permission) {
            RolePermission::query()->create(['role_id' => $role->id, 'permission' => $permission]);
        }

        return $role;
    }

    public static function grant(int $userId, string $slug): void
    {
        $roleId = Role::query()->where('slug', '=', $slug)->value('id');

        if ($roleId === null) {
            throw new RuntimeException(sprintf('There is no role with the slug "%s".', $slug));
        }

        self::grantRole($userId, (int) $roleId);
    }

    private static function grantRole(int $userId, int $roleId): void
    {
        UserRole::query()->create(
            [
                'user_id'    => $userId,
                'role_id'    => $roleId,
                'granted_at' => time(),
            ]
        );
    }
}
