<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Community\Domain\Repository\CommunityUserRepositoryInterface;
use Johncms\Users\User;

final class CommunityUserRepository implements CommunityUserRepositoryInterface
{
    public function paginateAdministrationUsers(int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->approved()
            ->where('rights', '>=', 1)
            ->orderByDesc('rights')
            ->paginate($perPage);
    }
}
