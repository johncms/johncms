<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;

final readonly class GetDeletePostFileContextUseCase
{
    public function __construct(
        private ForumFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(int $messageId, int $fileId): ForumFile
    {
        $file = $this->fileRepository->findByIdAndPostId($fileId, $messageId);
        if ($file === null) {
            throw new ForumNotFoundException('File not found.');
        }

        return $file;
    }
}
