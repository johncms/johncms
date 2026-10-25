<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Events\AuthEvent;
use Johncms\Modules\Admin\Domain\ValueObjects\AuthLogFilter;

interface AuthLogRepositoryInterface
{
    /**
     * @param int $now The moment the period of the filter is counted back from.
     */
    public function count(AuthLogFilter $filter, int $now): int;

    /**
     * The newest entries first.
     *
     * @return Collection<int, AuthEvent>
     */
    public function get(AuthLogFilter $filter, int $now, int $limit, int $offset): Collection;

    /**
     * The names behind the ids of a page, in one query.
     *
     * @param list<int> $ids
     *
     * @return array<int, string>
     */
    public function namesOf(array $ids): array;
}
