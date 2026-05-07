<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DownloadFileRepositoryInterface
{
    public function paginateNewFiles(int $page, int $perPage, ?string $directoryPrefix = null): LengthAwarePaginator;
}
