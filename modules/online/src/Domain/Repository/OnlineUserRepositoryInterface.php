<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\User;

interface OnlineUserRepositoryInterface
{
    public function countOnline(): int;

    /**
     * @return Collection<int, User>
     */
    public function getOnline(int $limit, int $offset): Collection;

    public function countHistory(): int;

    /**
     * @return Collection<int, User>
     */
    public function getHistory(int $limit, int $offset): Collection;
}
