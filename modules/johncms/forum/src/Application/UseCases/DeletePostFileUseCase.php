<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Storage\ForumAttachmentStorageInterface;

final readonly class DeletePostFileUseCase
{
    public function __construct(
        private ForumFileRepositoryInterface $fileRepository,
        private ForumAttachmentStorageInterface $attachments,
    ) {
    }

    public function execute(int $fileId, string $filename): void
    {
        $this->fileRepository->deleteById($fileId);

        $this->attachments->delete($filename);
    }
}
