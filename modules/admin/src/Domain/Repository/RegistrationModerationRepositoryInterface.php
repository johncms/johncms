<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RegistrationModerationRepositoryInterface
{
    /**
     * @return LengthAwarePaginator Пользователи, ожидающие подтверждения (preg = 0).
     */
    public function paginatePending(int $page, int $perPage): LengthAwarePaginator;

    public function approve(int $id, string $adminName): void;

    public function approveAll(string $adminName): void;

    public function delete(int $id): void;

    public function deleteAll(): void;

    public function deleteByIp(int $ip): void;
}
