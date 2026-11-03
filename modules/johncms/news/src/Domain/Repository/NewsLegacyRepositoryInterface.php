<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

interface NewsLegacyRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function sections(): array;

    /** @return list<array<string, mixed>> */
    public function articles(): array;

    public function mapDiscussions(int $articleId, int $itemId): void;

    public function verifyDiscussions(int $articleId, int $itemId): void;

    /** @param list<int> $fileIds */
    public function queueFiles(array $fileIds): void;
}
