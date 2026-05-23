<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Infrastructure\Persistence\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Users\User as UserModel;

final class DownloadFileRepository implements DownloadFileRepositoryInterface
{
    private const NEW_FILES_THRESHOLD_SECONDS = 259200; // 3 days

    public function paginateNewFiles(int $page, int $perPage, ?string $directoryPrefix = null): LengthAwarePaginator
    {
        $threshold = time() - self::NEW_FILES_THRESHOLD_SECONDS;

        $query = DownloadFile::query()->with('category')->where('type', 2)->where('time', '>', $threshold);

        if ($directoryPrefix !== null) {
            $query->where('dir', 'like', $directoryPrefix . '%');
        }

        return $query->orderByDesc('time')->paginate($perPage, page: $page);
    }

    public function getTopFiles(DownloadTopSort $sort, int $limit): Collection
    {
        return DownloadFile::query()
            ->with('category')
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
            ->with('category')
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
            ->with('category')
            ->where('type', 2)
            ->where('user_id', $userId)
            ->orderByDesc('time')
            ->paginate($perPage, page: $page);
    }

    public function paginateFavorites(int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return DownloadFile::query()
            ->with('category')
            ->join('download__bookmark', 'download__files.id', '=', 'download__bookmark.file_id')
            ->where('download__bookmark.user_id', $userId)
            ->select('download__files.*', 'download__bookmark.id as bid')
            ->orderByDesc('download__files.time')
            ->paginate($perPage, page: $page);
    }

    public function paginateCommentsReview(int $page, int $perPage): LengthAwarePaginator
    {
        return DownloadComment::query()
            ->leftJoin('users', 'download__comments.user_id', '=', 'users.id')
            ->leftJoin('download__files', 'download__comments.sub_id', '=', 'download__files.id')
            ->select(
                'download__comments.*',
                'download__comments.id as cid',
                'users.rights as user_rights',
                'download__files.rus_name'
            )
            ->orderByDesc('download__comments.time')
            ->paginate($perPage, page: $page);
    }

    public function findFile(int $id): ?DownloadFile
    {
        return DownloadFile::query()
            ->whereIn('type', [2, 3])
            ->find($id);
    }

    public function findFileWithCategory(int $id): ?DownloadFile
    {
        return DownloadFile::query()
            ->with('category')
            ->whereIn('type', [2, 3])
            ->find($id);
    }

    public function existsByCategoryAndSlug(int $categoryId, string $slug, ?int $excludeFileId = null): bool
    {
        return DownloadFile::query()
            ->where('refid', $categoryId)
            ->where('slug', $slug)
            ->when($excludeFileId !== null, fn ($q) => $q->where('id', '!=', $excludeFileId))
            ->exists();
    }

    public function findAdditionalFiles(int $fileId): Collection
    {
        return DownloadMoreFile::query()
            ->where('refid', $fileId)
            ->orderBy('time')
            ->get();
    }
}
