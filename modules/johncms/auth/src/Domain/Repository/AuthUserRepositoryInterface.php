<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Domain\Repository;

use Johncms\Users\User;

/**
 * The account behind the screens that run without a signed-in visitor: recovering a password and
 * confirming an address from the link in an e-mail.
 *
 * A contract of its own rather than the profile's: the module draws the screens a visitor without
 * a session needs, and it has to work on a site whose profiles are switched off.
 */
interface AuthUserRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * Find a user by the latinized login (name_lat).
     */
    public function findByNameLat(string $nameLat): ?User;

    /**
     * Store the already hashed password for the user.
     */
    public function updatePassword(int $id, string $hashedPassword): void;

    /**
     * Apply a previously requested email change: move new_email into mail and clear the pending state.
     */
    public function confirmNewEmail(int $id, string $newEmail): void;
}
