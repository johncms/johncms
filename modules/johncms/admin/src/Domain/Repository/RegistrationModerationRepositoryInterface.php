<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface RegistrationModerationRepositoryInterface
{
    /**
     * Количество пользователей, ожидающих подтверждения (preg = 0).
     */
    public function countPending(): int;

    public function approve(int $id, string $adminName): void;

    public function approveAll(string $adminName): void;

    public function delete(int $id): void;

    public function deleteAll(): void;

    public function deleteByIp(int $ip): void;
}
