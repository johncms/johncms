<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;

interface LibraryTextRepositoryInterface
{
    public function countTopByField(string $field): int;

    public function countTopByRating(): int;

    /** @return Collection<int, \Johncms\Modules\Library\Domain\Models\LibraryText> */
    public function getTopByField(string $field, int $limit): Collection;

    /** @return Collection<int, \Johncms\Modules\Library\Domain\Models\LibraryText> */
    public function getTopByRating(int $limit): Collection;

    public function countNew(): int;

    /** @return Collection<int, \Johncms\Modules\Library\Domain\Models\LibraryText> */
    public function getNew(int $page, int $perPage): Collection;

    public function searchCount(string $query, bool $inTitle): int;

    /** @return Collection<int, \Johncms\Modules\Library\Domain\Models\LibraryText> */
    public function search(string $query, bool $inTitle, int $page, int $perPage): Collection;
}
