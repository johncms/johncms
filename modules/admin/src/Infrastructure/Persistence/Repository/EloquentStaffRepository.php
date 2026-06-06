<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Admin\Domain\Repository\StaffRepositoryInterface;
use Johncms\Users\User;

final class EloquentStaffRepository implements StaffRepositoryInterface
{
    public function getStaff(): Collection
    {
        return User::query()
            ->where('rights', '>=', 1)
            ->orderBy('name')
            ->get();
    }
}
