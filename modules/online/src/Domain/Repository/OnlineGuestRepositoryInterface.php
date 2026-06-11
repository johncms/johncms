<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\GuestSession;

interface OnlineGuestRepositoryInterface
{
    public function countOnline(): int;

    /**
     * @return Collection<int, GuestSession>
     */
    public function getOnline(int $limit, int $offset): Collection;
}
