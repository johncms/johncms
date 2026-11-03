<?php

declare(strict_types=1);

namespace Johncms\Users\Repository;

use Johncms\Users\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findMany(array $ids): array
    {
        /** @var array<int, User> $users */
        $users = User::query()->whereIn('id', $ids)->get()->keyBy('id')->all();
        return $users;
    }

    public function find(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function registerGuestbookPost(User $user): void
    {
        User::query()
            ->where('id', $user->id)
            ->update(
                [
                    'postguest' => $user->postguest + 1,
                    'lastpost'  => time(),
                ]
            );
    }

    public function findByNameLat(string $nameLat): ?User
    {
        return User::query()->where('name_lat', '=', $nameLat)->first();
    }

    public function findByName(string $name): ?User
    {
        return User::query()->where('name', '=', $name)->first();
    }
}
