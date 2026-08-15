<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Users\User as UserModel;

final class DownloadFileRepository implements DownloadFileRepositoryInterface
{
    private const NEW_FILES_THRESHOLD_SECONDS = 259200; // 3 days

    public function countNewFiles(?string $directoryPrefix = null): int
    {
        return $this->newFilesQuery($directoryPrefix)->count();
    }

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getNewFiles(int $limit, int $offset, ?string $directoryPrefix = null): Collection
    {
        return $this->newFilesQuery($directoryPrefix)
            ->with('category')
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<DownloadFile>
     */
    private function newFilesQuery(?string $directoryPrefix): Builder
    {
        $threshold = time() - self::NEW_FILES_THRESHOLD_SECONDS;

        $query = DownloadFile::query()->where('type', 2)->where('time', '>', $threshold);

        if ($directoryPrefix !== null) {
            $query->where('dir', 'like', $directoryPrefix . '%');
        }

        return $query;
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

    public function countSearchFiles(string $query, bool $searchInDescription): int
    {
        return $this->searchFilesQuery($query, $searchInDescription)->count();
    }

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getSearchFiles(string $query, bool $searchInDescription, int $limit, int $offset): Collection
    {
        return $this->searchFilesQuery($query, $searchInDescription)
            ->with('category')
            ->orderBy('rus_name')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<DownloadFile>
     */
    private function searchFilesQuery(string $query, bool $searchInDescription): Builder
    {
        $like = '%' . strtr($query, ['_' => '\\_', '%' => '\\%', '*' => '%']) . '%';
        $column = $searchInDescription ? 'about' : 'rus_name';

        return DownloadFile::query()
            ->where('type', 2)
            ->where($column, 'like', $like);
    }

    public function countTopUsers(): int
    {
        return DownloadFile::query()
            ->where('type', '<>', 3)
            ->where('user_id', '>', 0)
            ->distinct()
            ->count('user_id');
    }

    /**
     * @return SupportCollection<int, UserModel>
     */
    public function getTopUsers(int $limit, int $offset): SupportCollection
    {
        // GROUP BY on download__files.user_id avoids MySQL ONLY_FULL_GROUP_BY issues
        $fileCountsByUserId = DownloadFile::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) AS files_count')
            ->where('type', '<>', 3)
            ->where('user_id', '>', 0)
            ->groupBy('user_id')
            ->orderByDesc('files_count')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->pluck('files_count', 'user_id');

        $userModels = UserModel::query()
            ->whereIn('id', $fileCountsByUserId->keys()->all())
            ->get()
            ->keyBy('id');

        return $fileCountsByUserId->keys()->map(function ($userId) use ($userModels, $fileCountsByUserId) {
            $user = $userModels->get($userId);
            if ($user !== null) {
                $user->files_count = $fileCountsByUserId[$userId];
            }
            return $user;
        })->filter()->values();
    }

    public function countUserFiles(int $userId): int
    {
        return DownloadFile::query()
            ->where('type', 2)
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getUserFiles(int $userId, int $limit, int $offset): Collection
    {
        return DownloadFile::query()
            ->with('category')
            ->where('type', 2)
            ->where('user_id', $userId)
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countFavorites(int $userId): int
    {
        return DownloadFile::query()
            ->join('download__bookmark', 'download__files.id', '=', 'download__bookmark.file_id')
            ->where('download__bookmark.user_id', $userId)
            ->count();
    }

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getFavorites(int $userId, int $limit, int $offset): Collection
    {
        return DownloadFile::query()
            ->with('category')
            ->join('download__bookmark', 'download__files.id', '=', 'download__bookmark.file_id')
            ->where('download__bookmark.user_id', $userId)
            ->select('download__files.*', 'download__bookmark.id as bid')
            ->orderByDesc('download__files.time')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countCommentsReview(): int
    {
        return DownloadComment::query()->count();
    }

    /**
     * @return Collection<int, DownloadComment>
     */
    public function getCommentsReview(int $limit, int $offset): Collection
    {
        return DownloadComment::query()
            ->leftJoin('users', 'download__comments.user_id', '=', 'users.id')
            ->leftJoin('download__files', 'download__comments.sub_id', '=', 'download__files.id')
            ->select(
                'download__comments.*',
                'download__comments.id as cid',
                'download__files.rus_name'
            )
            ->orderByDesc('download__comments.time')
            ->offset($offset)
            ->limit($limit)
            ->get();
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
