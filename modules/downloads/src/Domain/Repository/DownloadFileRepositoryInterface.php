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

    public function searchFiles(string $query, bool $searchInDescription, int $page, int $perPage): LengthAwarePaginator;

    public function paginateTopUsers(int $page, int $perPage): LengthAwarePaginator;

    public function paginateUserFiles(int $userId, int $page, int $perPage): LengthAwarePaginator;
}
