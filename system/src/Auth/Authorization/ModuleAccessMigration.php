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

/**
 * Turns the old "who may use this module" settings into permissions of the built-in roles.
 *
 * The mod_* keys were the second access system of the CMS, parallel to users.rights and unaware
 * of it: one number per module deciding whether guests, signed-in visitors or nobody could read
 * it and write in it. Each of those numbers is a couple of permissions of the guest and the user
 * roles now, and this is what carries a running site over without changing what it allows.
 *
 * Unlike DefaultPermissionsApplier this one also revokes: "the forum is for registered visitors"
 * means the guest role must *not* hold forum.view, and handing it back is exactly what the
 * migration has to avoid.
 */
final readonly class ModuleAccessMigration
{
    /**
     * Which value of which setting left which permission with the guest and the user roles.
     *
     * Read as: with `mod_forum` set to 2 or 3, the guest role holds forum.view; with any other
     * value it does not — a permission listed here is revoked when the value does not match.
     * A setting the site does not have is skipped altogether.
     *
     * @var array<string, array<string, array<string, list<int>>>>
     */
    private const RULES = [
        'mod_forum' => [
            SystemRole::Guest->value => [
                'forum.view' => [2, 3],
            ],
            SystemRole::User->value => [
                'forum.view' => [1, 2, 3],
                'forum.post' => [1, 2],
            ],
        ],
        'mod_guest' => [
            SystemRole::Guest->value => [
                'guestbook.view' => [1, 2],
                'guestbook.post' => [2],
            ],
            SystemRole::User->value => [
                'guestbook.view' => [1, 2],
                'guestbook.post' => [1, 2],
            ],
        ],
        'mod_lib' => [
            SystemRole::Guest->value => [
                'library.view' => [2],
            ],
            SystemRole::User->value => [
                'library.view' => [1, 2],
            ],
        ],
        'mod_down' => [
            SystemRole::Guest->value => [
                'downloads.view' => [2],
            ],
            SystemRole::User->value => [
                'downloads.view' => [1, 2],
            ],
        ],
    ];

    /**
     * What a module closed to everybody still left to the staff. Granted when the value matches
     * and never revoked otherwise: the staff roles hold these by default anyway, and taking one
     * away here would close the forum to its own moderators.
     *
     * These exceptions used to be written as "rights >= 7" inside the check of the module, which
     * is why they are not settings and have to be spelled out here.
     *
     * @var array<string, array<string, array<string, list<int>>>>
     */
    private const STAFF_EXCEPTIONS = [
        'mod_forum' => [
            SystemRole::Admin->value => [
                'forum.view' => [0],
            ],
        ],
        'mod_guest' => [
            SystemRole::Admin->value => [
                'guestbook.view' => [0],
                'guestbook.post' => [0],
            ],
        ],
        'mod_lib' => [
            SystemRole::Admin->value => [
                'library.view' => [0],
            ],
        ],
        'mod_down' => [
            SystemRole::Admin->value => [
                'downloads.view' => [0],
            ],
        ],
    ];

    public function __construct(private RoleRepositoryInterface $roles)
    {
    }

    /**
     * @param array<string, mixed> $config The johncms configuration, as the site has it now.
     * @return array<string, array{granted: list<string>, revoked: list<string>}> Per role slug;
     *                                     roles that needed no change are absent.
     */
    public function apply(array $config): array
    {
        $changes = [];

        foreach ($this->intended($config) as $slug => $intended) {
            $role = $this->roles->findBySlug($slug);

            if ($role === null) {
                continue;
            }

            $current = $this->roles->permissionsFor([$role->id]);
            $granted = array_values(array_diff($intended['grant'], $current));
            $revoked = array_values(array_intersect($intended['revoke'], $current));

            if ($granted === [] && $revoked === []) {
                continue;
            }

            $this->roles->setPermissions(
                $role->id,
                array_values(array_diff([...$current, ...$granted], $revoked))
            );

            $changes[$slug] = ['granted' => $granted, 'revoked' => $revoked];
        }

        return $changes;
    }

    /**
     * What the settings of this site add up to: the keys each role should end up holding, and
     * the ones it should end up without.
     *
     * @param array<string, mixed> $config
     * @return array<string, array{grant: list<string>, revoke: list<string>}>
     */
    private function intended(array $config): array
    {
        $intended = [];

        foreach (self::RULES as $key => $roles) {
            if (! array_key_exists($key, $config)) {
                continue;
            }

            foreach ($roles as $slug => $permissions) {
                foreach ($permissions as $permission => $values) {
                    $intended[$slug] ??= ['grant' => [], 'revoke' => []];
                    $matches = in_array((int) $config[$key], $values, true);
                    $intended[$slug][$matches ? 'grant' : 'revoke'][] = $permission;
                }
            }
        }

        foreach (self::STAFF_EXCEPTIONS as $key => $roles) {
            if (! array_key_exists($key, $config)) {
                continue;
            }

            foreach ($roles as $slug => $permissions) {
                foreach ($permissions as $permission => $values) {
                    if (! in_array((int) $config[$key], $values, true)) {
                        continue;
                    }

                    $intended[$slug] ??= ['grant' => [], 'revoke' => []];
                    $intended[$slug]['grant'][] = $permission;
                }
            }
        }

        return $intended;
    }
}
