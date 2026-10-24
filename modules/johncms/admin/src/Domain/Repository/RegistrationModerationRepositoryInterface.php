<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Users\User;

interface RegistrationModerationRepositoryInterface
{
    /**
     * Количество пользователей, ожидающих подтверждения (preg = 0).
     */
    public function countPending(): int;

    /**
     * The account, if it is still waiting for approval.
     */
    public function findPending(int $id): ?User;

    /**
     * The accounts waiting for approval that registered from this address (ip2long).
     */
    public function countPendingByIp(int $ip): int;

    public function approve(int $id, string $adminName): void;

    public function approveAll(string $adminName): void;

    /**
     * Confirms the e-mail of the account on its behalf, as the link mailed to it would.
     */
    public function confirmEmail(int $id): void;

    public function delete(int $id): void;

    public function deleteAll(): void;

    public function deleteByIp(int $ip): void;
}
