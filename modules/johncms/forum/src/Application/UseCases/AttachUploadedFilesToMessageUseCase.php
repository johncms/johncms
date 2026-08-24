<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Files\FileStore;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

final readonly class AttachUploadedFilesToMessageUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumMessageFileRepositoryInterface $messageFileRepository,
        private FileStore $files,
    ) {
    }

    /**
     * @param int[] $attachedFileIds
     */
    public function execute(int $messageId, array $attachedFileIds): void
    {
        $message = $this->messageRepository->findById($messageId);
        if ($message === null) {
            return;
        }

        $fileIds = array_values(
            array_unique(
                array_filter(
                    array_map(static fn ($id): int => (int) $id, $attachedFileIds),
                    static fn (int $id): bool => $id > 0,
                )
            )
        );

        if ($fileIds === []) {
            return;
        }

        $forumFileIds = $this->files->filterIdsInDirectory($fileIds, 'forum_files');
        if ($forumFileIds === []) {
            return;
        }

        $this->messageFileRepository->attachFilesToMessage($messageId, $forumFileIds);
    }
}
