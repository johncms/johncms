<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Users\User;

final class EloquentUserEditRepository implements UserEditRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function update(int $id, array $attributes): void
    {
        // Through a loaded model, so the casts of the columns apply.
        User::query()->find($id)?->update($attributes);
    }

    public function updatePassword(int $id, string $hash): void
    {
        User::query()->where('id', $id)->update(['password' => $hash]);
    }

    public function resetSettings(int $id): void
    {
        // Through a loaded model, so set_user and set_forum serialize the empty values.
        User::query()->find($id)?->update(['set_user' => [], 'set_forum' => []]);
    }
}
