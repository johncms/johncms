<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Infrastructure\Persistence\Repository;

use Johncms\Modules\Auth\Domain\Repository\AuthUserRepositoryInterface;
use Johncms\Users\User;

final class EloquentAuthUserRepository implements AuthUserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function findByNameLat(string $nameLat): ?User
    {
        return User::query()->where('name_lat', '=', $nameLat)->first();
    }

    public function updatePassword(int $id, string $hashedPassword): void
    {
        User::query()->where('id', '=', $id)->update(['password' => $hashedPassword]);
    }

    public function confirmNewEmail(int $id, string $newEmail): void
    {
        User::query()->where('id', '=', $id)->update([
            'mail'              => $newEmail,
            'new_email'         => null,
            'confirmation_code' => null,
        ]);
    }
}
