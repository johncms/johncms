<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Consent\Domain\Models\ConsentLog;
use Johncms\Modules\Consent\Domain\Repository\ConsentLogRepositoryInterface;

final class ConsentLogRepository implements ConsentLogRepositoryInterface
{
    public function create(array $data): ConsentLog
    {
        return ConsentLog::query()->create($data);
    }

    public function getPage(int $limit, int $offset): Collection
    {
        return ConsentLog::query()
            ->with('consent')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function count(): int
    {
        return ConsentLog::query()->count();
    }
}
