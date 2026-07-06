<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;

interface ContentCollectionRepositoryInterface
{
    /**
     * Resolve a collection by its machine code (also used by the URL resolver
     * to match the root URL segment).
     */
    public function findByCode(string $code): ?ContentCollection;

    public function findById(int $id): ?ContentCollection;

    /**
     * @return Collection<int, ContentCollection>
     */
    public function getAll(int $limit, int $offset): Collection;

    public function countAll(): int;

    /**
     * Map of active collection code => id, used by the public URL resolver.
     *
     * @return array<string, int>
     */
    public function getActiveCodeMap(): array;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ContentCollection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): void;

    public function delete(int $id): void;
}
