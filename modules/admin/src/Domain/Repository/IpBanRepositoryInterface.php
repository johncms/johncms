<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Models\BanIp;

interface IpBanRepositoryInterface
{
    public function count(): int;

    /**
     * @return EloquentCollection<int, BanIp>
     */
    public function get(int $limit, int $offset): EloquentCollection;

    public function findById(int $id): ?BanIp;

    public function findByIp(int $ip): ?BanIp;

    /**
     * Баны, пересекающиеся с заданным диапазоном.
     *
     * @return Collection<int, BanIp>
     */
    public function findConflicts(int $ip1, int $ip2): Collection;

    public function create(int $ip1, int $ip2, int $banType, string $link, string $who, string $reason): void;

    public function deleteById(int $id): void;

    public function clearAll(): void;
}
