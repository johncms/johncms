<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

interface NewsLegacyStorageRepositoryInterface
{
    public function prepare(): void;
    public function hasData(): bool;
    public function clearLegacyRows(): void;
    /** The comments of the articles already mapped go into the comments of the core. */
    public function moveDiscussionsToCore(): void;

    public function removeOrphanDiscussions(): void;

    /** Whether a comment of the core holds the file. */
    public function isHeldByComment(int $fileId): bool;
    /** @return list<int> */
    public function pendingFiles(): array;
    public function acknowledgeFile(int $id): void;
    public function dropLegacySchema(): void;
    public function hasLegacySchema(): bool;
}
