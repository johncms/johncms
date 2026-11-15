<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

/**
 * Brings a hidden topic back into sight with the files of its posts; posts hidden on their own stay
 * hidden, and so do their files.
 */
final readonly class RestoreTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(int $topicId, string $restoredBy): void
    {
        $this->topicRepository->restoreById($topicId, $restoredBy);
        $this->fileRepository->restoreOfVisiblePostsByTopicId($topicId);
    }
}
