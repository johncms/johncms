<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;

interface DownloadFileRepositoryInterface
{
    public function paginateNewFiles(int $page, int $perPage, ?string $directoryPrefix = null): LengthAwarePaginator;

    public function getTopFiles(DownloadTopSort $sort, int $limit): Collection;
}
