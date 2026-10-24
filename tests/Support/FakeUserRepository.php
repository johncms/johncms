<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Users\User;

/**
 * Hands out the users a test prepared in memory, so asking for the current profile needs no
 * schema.
 */
final class FakeUserRepository implements UserRepositoryInterface
{
    /** @var array<int, User> */
    private array $users = [];

    /**
     * @param list<User> $users Keyed by their own id.
     */
    public function __construct(array $users = [])
    {
        foreach ($users as $user) {
            $this->users[(int) $user->id] = $user;
        }
    }

    public function find(int $id): ?User
    {
        return $this->users[$id] ?? null;
    }

    public function findByNameLat(string $nameLat): ?User
    {
        return array_find($this->users, static fn (User $user): bool => $user->name_lat === $nameLat);
    }

    public function findByName(string $name): ?User
    {
        return array_find($this->users, static fn (User $user): bool => $user->name === $name);
    }

    public function registerGuestbookPost(User $user): void
    {
    }
}
