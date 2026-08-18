<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Files\FileStore;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Throwable;

final readonly class DeleteTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumFileRepositoryInterface $fileRepository,
        private ForumMessageFileRepositoryInterface $messageFileRepository,
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumVoteRepositoryInterface $voteRepository,
        private ForumUnreadRepositoryInterface $unreadRepository,
        private FileStore $files,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function hideTopic(int $topicId, string $deletedBy): void
    {
        $this->topicRepository->markDeleted($topicId, $deletedBy);
        $this->fileRepository->markDeletedByTopicId($topicId);
    }

    /**
     * @throws Throwable
     */
    public function deleteTopic(int $topicId): void
    {
        $files = $this->fileRepository->getByTopicId($topicId);
        $linkedFileIds = $this->messageFileRepository->getFileIdsByTopicId($topicId);

        Capsule::connection()->transaction(function () use ($topicId): void {
            $this->messageFileRepository->deleteByTopicId($topicId);
            $this->fileRepository->deleteByTopicId($topicId);
            $this->messageRepository->deleteByTopicId($topicId);
            $this->voteRepository->deleteVotesByTopic($topicId);
            $this->voteRepository->deleteVoteUsersByTopic($topicId);
            $this->unreadRepository->deleteByTopicId($topicId);
            $this->topicRepository->deleteById($topicId);
        });

        foreach ($files as $file) {
            $this->attachments->delete((string) $file->filename);
        }

        $this->files->deleteMany($this->messageFileRepository->getOrphanedFileIds($linkedFileIds));
    }
}
