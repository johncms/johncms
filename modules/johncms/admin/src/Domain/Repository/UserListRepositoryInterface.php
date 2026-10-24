<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Admin\Domain\ValueObjects\UserListFilter;
use Johncms\Users\User;

interface UserListRepositoryInterface
{
    public function findById(int $id): ?User;

    /**
     * How many accounts the filter lets through. Its order plays no part.
     */
    public function count(UserListFilter $filter, int $now): int;

    /**
     * One page of the accounts the filter lets through, in its order.
     *
     * @return Collection<int, User>
     */
    public function getPage(UserListFilter $filter, int $now, int $limit, int $offset): Collection;

    /**
     * When the ban in force on each of these accounts ends. Accounts under no ban are absent.
     *
     * @param list<int> $userIds
     * @return array<int, int> User id => the end of the ban, a timestamp.
     */
    public function getBanEnds(array $userIds, int $now): array;
}
