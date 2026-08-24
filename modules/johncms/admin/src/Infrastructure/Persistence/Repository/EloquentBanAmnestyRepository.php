<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Johncms\Modules\Admin\Domain\Repository\BanAmnestyRepositoryInterface;
use Johncms\Users\Ban;

final class EloquentBanAmnestyRepository implements BanAmnestyRepositoryInterface
{
    private const MONTH = 2592000;

    public function clearAllBans(): void
    {
        Ban::query()->truncate();
    }

    public function unbanActiveShortTerm(string $reason): void
    {
        $now = time();

        Ban::query()
            ->where('ban_time', '>', $now)
            ->where('ban_time', '<', $now + self::MONTH)
            ->update(['ban_time' => $now, 'ban_raz' => $reason]);
    }
}
