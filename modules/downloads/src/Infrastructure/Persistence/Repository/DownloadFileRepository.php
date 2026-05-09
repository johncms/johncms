<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Users\User as UserModel;

final class DownloadFileRepository implements DownloadFileRepositoryInterface
{
    private const NEW_FILES_THRESHOLD_SECONDS = 259200; // 3 days

    public function paginateNewFiles(int $page, int $perPage, ?string $directoryPrefix = null): LengthAwarePaginator
    {
        $threshold = time() - self::NEW_FILES_THRESHOLD_SECONDS;

        $query = DownloadFile::query()->where('type', 2)->where('time', '>', $threshold);

        if ($directoryPrefix !== null) {
            $query->where('dir', 'like', $directoryPrefix . '%');
        }

        return $query->orderByDesc('time')->paginate($perPage, page: $page);
    }

    public function getTopFiles(DownloadTopSort $sort, int $limit): Collection
    {
        return DownloadFile::query()
            ->where('type', 2)
            ->orderByDesc($sort->column())
            ->limit($limit)
            ->get();
    }

    public function searchFiles(string $query, bool $searchInDescription, int $page, int $perPage): LengthAwarePaginator
    {
        $like = '%' . strtr($query, ['_' => '\\_', '%' => '\\%', '*' => '%']) . '%';
        $column = $searchInDescription ? 'about' : 'rus_name';

        return DownloadFile::query()
            ->where('type', 2)
            ->where($column, 'like', $like)
            ->orderBy('rus_name')
            ->paginate($perPage, page: $page);
    }

    public function paginateTopUsers(int $page, int $perPage): LengthAwarePaginator
    {
        // GROUP BY on download__files.user_id avoids MySQL ONLY_FULL_GROUP_BY issues
        $filesPaginator = DownloadFile::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) AS files_count')
            ->where('type', '<>', 3)
            ->where('user_id', '>', 0)
            ->groupBy('user_id')
            ->orderByDesc('files_count')
            ->paginate($perPage, page: $page);

        $fileCountsByUserId = collect($filesPaginator->items())
            ->pluck('files_count', 'user_id');

        $userModels = UserModel::query()
            ->whereIn('id', $fileCountsByUserId->keys()->all())
            ->get()
            ->keyBy('id');

        $items = $fileCountsByUserId->keys()->map(function ($userId) use ($userModels, $fileCountsByUserId) {
            $user = $userModels->get($userId);
            if ($user !== null) {
                $user->files_count = $fileCountsByUserId[$userId];
            }
            return $user;
        })->filter()->values();

        return new ConcretePaginator($items, $filesPaginator->total(), $perPage, $page);
    }

    public function paginateUserFiles(int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return DownloadFile::query()
            ->where('type', 2)
            ->where('user_id', $userId)
            ->orderByDesc('time')
            ->paginate($perPage, page: $page);
    }
}
