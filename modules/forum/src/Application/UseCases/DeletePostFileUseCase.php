<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;

final readonly class DeletePostFileUseCase
{
    public function __construct(
        private ForumFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(int $fileId, string $filename): void
    {
        $this->fileRepository->deleteById($fileId);

        $filePath = UPLOAD_PATH . 'forum/attach/' . $filename;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}
