<?php

declare(strict_types=1);

namespace Tests\Unit\Files;

use Illuminate\Support\Collection;
use Johncms\Files\FileRepositoryInterface;
use Johncms\Files\StoredFile;
use Throwable;

/**
 * The file registry without a database: rows in an array.
 *
 * A fake rather than a mock, because what these tests are about is the state left behind — a
 * row inserted, a row gone — and expectations on calls would assert the opposite of that.
 */
final class InMemoryFileRepository implements FileRepositoryInterface
{
    /** @var array<int, StoredFile> */
    private array $files = [];

    private int $nextId = 1;

    /** Set to have the next create() fail, the way a database error would. */
    public ?Throwable $failOnCreate = null;

    public function add(int $id, string $path, int $size = 1, string $disk = 'local'): StoredFile
    {
        $file = new StoredFile(['storage' => $disk, 'name' => basename($path), 'path' => $path, 'size' => $size]);
        $file->id = $id;
        $this->files[$id] = $file;
        $this->nextId = max($this->nextId, $id + 1);

        return $file;
    }

    /** @return list<int> */
    public function ids(): array
    {
        return array_values(array_map(static fn(StoredFile $file): int => $file->id, $this->files));
    }

    public function findById(int $id): ?StoredFile
    {
        return $this->files[$id] ?? null;
    }

    public function getByIds(array $ids): Collection
    {
        $found = [];
        foreach ($ids as $id) {
            if (isset($this->files[$id])) {
                $found[] = $this->files[$id];
            }
        }

        return new Collection($found);
    }

    public function create(array $attributes): StoredFile
    {
        if ($this->failOnCreate !== null) {
            throw $this->failOnCreate;
        }

        $file = new StoredFile($attributes);
        $file->id = $this->nextId++;
        $this->files[$file->id] = $file;

        return $file;
    }

    public function deleteByIds(array $ids): void
    {
        foreach ($ids as $id) {
            unset($this->files[$id]);
        }
    }

    public function getIdsInDirectory(array $ids, string $directory): array
    {
        $prefix = rtrim($directory, '/') . '/';

        $found = [];
        foreach ($ids as $id) {
            if (isset($this->files[$id]) && str_starts_with($this->files[$id]->path, $prefix)) {
                $found[] = $id;
            }
        }

        return $found;
    }
}
