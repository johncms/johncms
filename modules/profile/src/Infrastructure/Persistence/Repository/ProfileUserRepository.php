<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;

final class ProfileUserRepository implements ProfileUserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function markGuestbookSeen(int $userId, int $commCount): void
    {
        User::query()->where('id', '=', $userId)->update(['comm_old' => $commCount]);
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
