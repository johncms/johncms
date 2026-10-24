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

use Johncms\Users\Repository\UserRepositoryInterface;

/**
 * The account a visitor names when signing in or recovering a password: by the nickname as it is
 * typed, or else by the name it signs in under (LoginName) — and the login name of an account
 * found the first way, brought in line.
 *
 * The nickname comes first for the accounts whose login name was derived some other way: older
 * versions transliterated the nickname by a table of their own and kept the spaces and the
 * punctuation, the installer only lowercased it, and a nickname of nothing but symbols has no
 * login name at all. What LoginName makes of such a nickname may be the login name of another
 * account; the nickname itself is never another's.
 */
final readonly class AccountByLogin
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function find(string $login): ?User
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        $user = $this->users->findByName($login);
        if ($user !== null) {
            return $user;
        }

        $loginName = LoginName::of($login);

        return $loginName !== '' ? $this->users->findByNameLat($loginName) : null;
    }

    /**
     * An account gets the login name the rest of the site derives, the way an outdated hash is
     * replaced: as its owner comes back. Unless another account holds that name already — then
     * the nickname keeps signing it in.
     */
    public function renewLoginName(User $user): void
    {
        $loginName = LoginName::of((string) $user->name);

        if ($loginName === '' || $loginName === $user->name_lat) {
            return;
        }

        $holder = $this->users->findByNameLat($loginName);
        if ($holder !== null && $holder->id !== $user->id) {
            return;
        }

        $user->name_lat = $loginName;
        $user->save();
    }
}
