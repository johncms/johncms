<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use DateTimeImmutable;
use Johncms\Database\TransactionManagerInterface;
use Johncms\Files\FileStore;

/**
 * Removes the editor pictures nobody is going to send, in the directories the consumers declared.
 *
 * Two passes. The uploads whose form was never sent are locked and their rows removed in one
 * transaction, so a form sent at the same moment either takes the picture first or finds it gone
 * and says so; the files go after the commit. Then the files of those directories that no row
 * knows: left by a process that stopped between removing a row and its file, or between writing a
 * file and its row. Both only look at what is older than the threshold, so an upload in progress
 * is never touched.
 */
final readonly class CleanupEditorUploadsUseCase
{
    public function __construct(
        private EditorUploadConsumers $consumers,
        private EditorUploadRepositoryInterface $uploads,
        private TransactionManagerInterface $transactions,
        private FileStore $files,
    ) {
    }

    /**
     * @return array{abandoned: int, untracked: int}
     */
    public function execute(int $ttlHours = 24, int $batchSize = 500): array
    {
        $directories = $this->consumers->directories();
        $threshold = new DateTimeImmutable('-' . max(1, $ttlHours) . ' hours');
        $batchSize = max(1, $batchSize);

        $abandoned = $this->transactions->run(
            fn(): array => $this->uploads->releaseAbandoned($directories, $threshold, $batchSize)
        );
        $this->files->deleteMany($abandoned);

        $untracked = $this->uploads->getUntrackedFileIds($directories, $threshold, $batchSize);
        $this->files->deleteMany($untracked);

        return ['abandoned' => count($abandoned), 'untracked' => count($untracked)];
    }
}
