<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

interface NewsLegacyStorageRepositoryInterface
{
    public function prepare(): void;
    public function hasData(): bool;
    public function clearLegacyRows(): void;
    public function removeOrphanDiscussions(): void;
    /** @return list<int> */
    public function pendingFiles(): array;
    public function acknowledgeFile(int $id): void;
    public function dropLegacySchema(): void;
    public function hasLegacySchema(): bool;
}
