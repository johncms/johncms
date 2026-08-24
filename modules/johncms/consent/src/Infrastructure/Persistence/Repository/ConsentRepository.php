<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Consent\Domain\Models\Consent;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;

final class ConsentRepository implements ConsentRepositoryInterface
{
    public function getActiveByContext(string $context, string $language): Collection
    {
        return Consent::query()
            ->where('context', $context)
            ->where('language', $language)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function findById(int $id): ?Consent
    {
        return Consent::query()->find($id);
    }

    public function getPage(int $limit, int $offset): Collection
    {
        return Consent::query()
            ->orderBy('context')
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function count(): int
    {
        return Consent::query()->count();
    }
}
