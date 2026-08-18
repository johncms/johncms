<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Files\FileRepositoryInterface;
use Johncms\Files\StoredFile;

final class EloquentFileRepository implements FileRepositoryInterface
{
    public function findById(int $id): ?StoredFile
    {
        return StoredFile::query()->find($id);
    }

    public function getByIds(array $ids): Collection
    {
        if ($ids === []) {
            return new Collection();
        }

        /** @var Collection<int, StoredFile> $files Static analysis loses the model type through whereIn(). */
        $files = StoredFile::query()->whereIn('id', $ids)->get();

        return $files;
    }

    public function create(array $attributes): StoredFile
    {
        return StoredFile::query()->create($attributes);
    }

    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        StoredFile::query()->whereIn('id', $ids)->delete();
    }

    public function getIdsInDirectory(array $ids, string $directory): array
    {
        if ($ids === []) {
            return [];
        }

        return StoredFile::query()
            ->whereIn('id', $ids)
            ->where('path', 'like', rtrim($directory, '/') . '/%')
            ->pluck('id')
            ->all();
    }
}
