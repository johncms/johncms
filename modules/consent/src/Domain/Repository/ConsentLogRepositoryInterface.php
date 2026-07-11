<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Consent\Domain\Models\ConsentLog;

interface ConsentLogRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): ConsentLog;

    /**
     * A page of log entries (with the related consent eager-loaded), newest first.
     *
     * @return Collection<int, ConsentLog>
     */
    public function getPage(int $limit, int $offset): Collection;

    public function count(): int;
}
