<?php

declare(strict_types=1);

namespace Johncms\Users\Repository;

use Johncms\Users\User;

interface UserRepositoryInterface
{
    /**
     * The user behind the id, or null when there is no such row.
     */
    public function find(int $id): ?User;

    /**
     * Register a new guestbook post for the user: increment the postguest
     * counter and refresh the last post timestamp.
     */
    public function registerGuestbookPost(User $user): void;

    /**
     * The account signing in under this name (users.name_lat).
     */
    public function findByNameLat(string $nameLat): ?User;

    /**
     * The account with exactly this nickname. Nicknames are unique.
     */
    public function findByName(string $name): ?User;
}
