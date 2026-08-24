<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

interface ForumMessageFileRepositoryInterface
{
    /**
     * @param int[] $fileIds
     */
    public function attachFilesToMessage(int $messageId, array $fileIds): void;

    /**
     * @return int[]
     */
    public function getFileIdsByMessageId(int $messageId): array;

    /**
     * @return int[]
     */
    public function getFileIdsByTopicId(int $topicId): array;

    public function deleteByMessageId(int $messageId): void;

    public function deleteByTopicId(int $topicId): void;

    /**
     * @param int[] $fileIds
     * @return int[]
     */
    public function getOrphanedFileIds(array $fileIds): array;

    /**
     * @return int[]
     */
    public function getOrphanStorageFileIds(string $pathPrefix, string $createdBefore, int $limit): array;
}
