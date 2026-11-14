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

    /** Whether a comment not yet carried into the core names the file among its attachments. */
    public function commentsReferenceFile(int $fileId): bool;

    /** @param list<int> $fileIds */
    public function queueFiles(array $fileIds): void;
}
