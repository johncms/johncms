<?php

declare(strict_types=1);

namespace Johncms\Users\Repository;

use Johncms\Users\User;

class EloquentUserRepository implements UserRepositoryInterface
{
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
}
