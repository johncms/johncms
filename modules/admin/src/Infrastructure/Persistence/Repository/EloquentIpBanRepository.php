<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Models\BanIp;
use Johncms\Modules\Admin\Domain\Repository\IpBanRepositoryInterface;

final class EloquentIpBanRepository implements IpBanRepositoryInterface
{
    public function count(): int
    {
        return BanIp::query()->count();
    }

    public function paginate(int $page, int $perPage): LengthAwarePaginator
    {
        return BanIp::query()
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById(int $id): ?BanIp
    {
        return BanIp::query()->find($id);
    }

    public function findByIp(int $ip): ?BanIp
    {
        return BanIp::query()
            ->whereRaw('? BETWEEN `ip1` AND `ip2`', [$ip])
            ->first();
    }

    public function findConflicts(int $ip1, int $ip2): Collection
    {
        return BanIp::query()
            ->whereRaw('? BETWEEN `ip1` AND `ip2`', [$ip1])
            ->orWhereRaw('? BETWEEN `ip1` AND `ip2`', [$ip2])
            ->orWhere(function ($query) use ($ip1, $ip2): void {
                $query->where('ip1', '>=', $ip1)->where('ip2', '<=', $ip2);
            })
            ->get();
    }

    public function create(int $ip1, int $ip2, int $banType, string $link, string $who, string $reason): void
    {
        BanIp::query()->create([
            'ip1'      => $ip1,
            'ip2'      => $ip2,
            'ban_type' => $banType,
            'link'     => $link,
            'who'      => $who,
            'reason'   => $reason,
            'date'     => time(),
        ]);
    }

    public function deleteById(int $id): void
    {
        BanIp::query()->where('id', $id)->delete();
    }

    public function clearAll(): void
    {
        BanIp::query()->truncate();
    }
}
