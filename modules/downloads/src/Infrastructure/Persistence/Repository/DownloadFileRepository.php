<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final class DownloadFileRepository implements DownloadFileRepositoryInterface
{
    private const NEW_FILES_THRESHOLD_SECONDS = 259200; // 3 days

    public function paginateNewFiles(int $page, int $perPage, ?string $directoryPrefix = null): LengthAwarePaginator
    {
        $threshold = time() - self::NEW_FILES_THRESHOLD_SECONDS;

        $query = DownloadFile::where('type', 2)->where('time', '>', $threshold);

        if ($directoryPrefix !== null) {
            $query->where('dir', 'like', $directoryPrefix . '%');
        }

        return $query->orderByDesc('time')->paginate($perPage, page: $page);
    }

    public function getTopFiles(DownloadTopSort $sort, int $limit): Collection
    {
        return DownloadFile::where('type', 2)
            ->orderByDesc($sort->column())
            ->limit($limit)
            ->get();
    }
}
