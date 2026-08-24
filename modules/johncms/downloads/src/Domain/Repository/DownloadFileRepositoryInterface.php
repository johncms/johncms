<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Users\User as UserModel;

interface DownloadFileRepositoryInterface
{
    public function countNewFiles(?string $directoryPrefix = null): int;

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getNewFiles(int $limit, int $offset, ?string $directoryPrefix = null): Collection;

    public function getTopFiles(DownloadTopSort $sort, int $limit): Collection;

    public function countSearchFiles(string $query, bool $searchInDescription): int;

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getSearchFiles(string $query, bool $searchInDescription, int $limit, int $offset): Collection;

    public function countTopUsers(): int;

    /**
     * @return SupportCollection<int, UserModel>
     */
    public function getTopUsers(int $limit, int $offset): SupportCollection;

    public function countUserFiles(int $userId): int;

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getUserFiles(int $userId, int $limit, int $offset): Collection;

    public function countFavorites(int $userId): int;

    /**
     * @return Collection<int, DownloadFile>
     */
    public function getFavorites(int $userId, int $limit, int $offset): Collection;

    public function countCommentsReview(): int;

    /**
     * @return Collection<int, DownloadComment>
     */
    public function getCommentsReview(int $limit, int $offset): Collection;

    public function findFile(int $id): ?DownloadFile;

    public function findFileWithCategory(int $id): ?DownloadFile;

    public function existsByCategoryAndSlug(int $categoryId, string $slug, ?int $excludeFileId = null): bool;

    public function findAdditionalFiles(int $fileId): Collection;
}
