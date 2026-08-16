<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Events\AuthEvent;

interface AuthLogRepositoryInterface
{
    /**
     * @param int|null    $userId Entries about one account, including the ones somebody else made
     *                            about it.
     * @param string|null $event  One kind of event, by its key.
     */
    public function count(?int $userId = null, ?string $event = null): int;

    /**
     * @return Collection<int, AuthEvent>
     */
    public function get(int $limit, int $offset, ?int $userId = null, ?string $event = null): Collection;

    /**
     * The names behind the ids of a page, in one query.
     *
     * @param list<int> $ids
     *
     * @return array<int, string>
     */
    public function namesOf(array $ids): array;
}
