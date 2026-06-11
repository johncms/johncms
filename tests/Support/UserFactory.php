<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Users\User;
use ReflectionProperty;

final class UserFactory
{
    /**
     * Builds an in-memory User model without touching the database.
     *
     * The `ban` accessor lazily queries the database when `active_bans` is empty,
     * so both private trait properties are pre-filled via reflection.
     *
     * Note: the `is_valid` accessor reads `config('johncms.user_email_confirmation')`,
     * so tests must call `ConfigRepository::init([...])` in `setUp()`.
     *
     * @param int[] $banTypes
     * @param array<string, mixed> $attributes
     */
    public static function make(int $rights = 0, bool $valid = true, array $banTypes = [], array $attributes = []): User
    {
        $user = new User(array_merge([
            'id'       => $valid ? 1 : 0,
            'name'     => 'TestUser',
            'rights'   => $rights,
            'preg'     => $valid,
            'lastdate' => time(),
        ], $attributes));

        self::prefillBans($user, $banTypes);

        return $user;
    }

    /**
     * @param int[] $banTypes
     */
    private static function prefillBans(User $user, array $banTypes): void
    {
        $activeBans = new ReflectionProperty(User::class, 'active_bans');
        $activeBans->setValue($user, [true]);

        $banList = new ReflectionProperty(User::class, 'ban_list');
        $banList->setValue($user, array_fill_keys($banTypes, 1));
    }
}
