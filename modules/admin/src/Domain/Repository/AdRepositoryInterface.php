<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Admin\Domain\Models\Ad;

interface AdRepositoryInterface
{
    public function countByType(int $type): int;

    public function paginateByType(int $type, int $page, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Ad;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): void;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): void;

    public function nextPlace(): int;

    public function delete(int $id): void;

    public function deleteInactive(): void;

    public function toggleActive(int $id): void;

    public function moveUp(int $id): void;

    public function moveDown(int $id): void;
}
