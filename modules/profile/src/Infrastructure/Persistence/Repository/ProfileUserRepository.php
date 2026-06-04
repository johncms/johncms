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

    public function updatePassword(int $id, string $hashedPassword): void
    {
        User::query()->where('id', '=', $id)->update(['password' => $hashedPassword]);
    }

    public function resetSettings(int $id): void
    {
        // Update through a loaded model so the set_user/set_forum casts serialize the empty values correctly
        User::query()->find($id)?->update([
            'set_user'  => [],
            'set_forum' => [],
        ]);
    }

    public function updateProfile(int $id, array $attributes): void
    {
        // Update through a loaded model so the attribute casts (admin_notes, mailvis, etc.) are applied
        User::query()->find($id)?->update($attributes);
    }
}
