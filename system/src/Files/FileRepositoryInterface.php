<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use Illuminate\Support\Collection;

/**
 * The file registry — the `files` table and nothing else.
 *
 * It knows nothing about disks: writing a file and registering it belong together, and the one
 * place they are put together is FileStore.
 */
interface FileRepositoryInterface
{
    public function findById(int $id): ?StoredFile;

    /**
     * @param list<int> $ids
     * @return Collection<int, StoredFile>
     */
    public function getByIds(array $ids): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): StoredFile;

    /**
     * @param list<int> $ids
     */
    public function deleteByIds(array $ids): void;

    /**
     * Of the given files, the ones stored under the directory.
     *
     * A module attaching files to its own records uses this to check that the ids it was handed
     * are files of its own upload directory and not somebody else's — which is a question about
     * the registry, so it is answered here rather than by a module querying the table itself.
     *
     * @param list<int> $ids
     * @return list<int>
     */
    public function getIdsInDirectory(array $ids, string $directory): array;
}
