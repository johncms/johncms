<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Auth\Events\AuthEvent;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Modules\Admin\Domain\Repository\SecurityOverviewRepositoryInterface;
use Johncms\Users\Ban;

final class EloquentSecurityOverviewRepository implements SecurityOverviewRepositoryInterface
{
    public function countFailedSignInsSince(int $since): int
    {
        return AuthEvent::query()
            ->where('event', '=', AuthEventType::LoginFailed->value)
            ->where('created_at', '>', $since)
            ->count();
    }

    public function getTopFailedIps(int $since, int $limit): array
    {
        $rows = AuthEvent::query()
            ->toBase()
            ->select('ip')
            ->selectRaw('COUNT(*) AS attempts')
            ->where('event', '=', AuthEventType::LoginFailed->value)
            ->where('created_at', '>', $since)
            ->where('ip', '<>', '')
            ->groupBy('ip')
            ->orderByDesc('attempts')
            ->orderBy('ip')
            ->limit($limit)
            ->get();

        $attempts = [];
        foreach ($rows as $row) {
            $attempts[(string) $row->ip] = (int) $row->attempts;
        }

        return $attempts;
    }

    public function countIpsWithFailedSignInsSince(int $since, int $atLeast): int
    {
        return AuthEvent::query()
            ->toBase()
            ->select('ip')
            ->where('event', '=', AuthEventType::LoginFailed->value)
            ->where('created_at', '>', $since)
            ->where('ip', '<>', '')
            ->groupBy('ip')
            ->havingRaw('COUNT(*) >= ?', [$atLeast])
            ->get()
            ->count();
    }

    public function getStaffSignIns(int $now, int $limit): Collection
    {
        $staff = UserRole::query()
            ->select('user_id')
            ->where(static function (Builder $query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            });

        return AuthEvent::query()
            ->leftJoin('users', 'auth_events.user_id', '=', 'users.id')
            ->select('auth_events.*', 'users.name as user_name')
            ->where('auth_events.event', '=', AuthEventType::LoginSuccess->value)
            ->whereIn('auth_events.user_id', $staff)
            ->orderByDesc('auth_events.created_at')
            ->orderByDesc('auth_events.id')
            ->limit($limit)
            ->get();
    }

    public function countActiveBans(int $now): int
    {
        return Ban::query()->where('ban_time', '>', $now)->count();
    }

    public function countBansIssuedSince(int $since): int
    {
        return Ban::query()->where('ban_while', '>', $since)->count();
    }
}
