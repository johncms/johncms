<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Users\User;

interface UserEditRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * @param array<string, mixed> $attributes Columns of the account and their new values.
     */
    public function update(int $id, array $attributes): void;

    public function updatePassword(int $id, string $hash): void;

    /**
     * Puts the settings of the account and of its forum back to the defaults.
     */
    public function resetSettings(int $id): void;
}
