<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Consent\Domain\Models\Consent;

interface ConsentRepositoryInterface
{
    /**
     * Active consents for a given context and language, ordered for display.
     *
     * @return Collection<int, Consent>
     */
    public function getActiveByContext(string $context, string $language): Collection;

    public function findById(int $id): ?Consent;

    /**
     * A page of consents for the admin list.
     *
     * @return Collection<int, Consent>
     */
    public function getPage(int $limit, int $offset): Collection;

    public function count(): int;
}
