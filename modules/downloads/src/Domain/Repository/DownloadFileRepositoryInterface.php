<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;

interface DownloadFileRepositoryInterface
{
    public function paginateNewFiles(int $page, int $perPage, ?string $directoryPrefix = null): LengthAwarePaginator;

    public function getTopFiles(DownloadTopSort $sort, int $limit): Collection;

    public function searchFiles(string $query, bool $searchInDescription, int $page, int $perPage): LengthAwarePaginator;

    public function paginateTopUsers(int $page, int $perPage): LengthAwarePaginator;

    public function paginateUserFiles(int $userId, int $page, int $perPage): LengthAwarePaginator;

    public function paginateFavorites(int $userId, int $page, int $perPage): LengthAwarePaginator;

    public function paginateCommentsReview(int $page, int $perPage): LengthAwarePaginator;

    public function findFile(int $id): ?DownloadFile;

    public function findFileWithCategory(int $id): ?DownloadFile;

    public function existsByCategoryAndSlug(int $categoryId, string $slug, ?int $excludeFileId = null): bool;

    public function findAdditionalFiles(int $fileId): Collection;
}
