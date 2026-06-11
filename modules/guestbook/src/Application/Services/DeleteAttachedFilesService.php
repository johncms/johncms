<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use Johncms\Files\FileStorage;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class DeleteAttachedFilesService
{
    public function __construct(
        private FileStorage $storage,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param mixed[] $attachedFiles
     */
    public function delete(array $attachedFiles, ?int $entryId = null): void
    {
        foreach ($attachedFiles as $attachedFile) {
            $fileId = filter_var($attachedFile, FILTER_VALIDATE_INT);
            if ($fileId === false) {
                continue;
            }

            try {
                $this->storage->delete($fileId);
            } catch (Throwable $exception) {
                $this->logger->error($exception->getMessage(), [
                    'exception' => $exception,
                    'file'      => $attachedFile,
                    'post_id'   => $entryId,
                ]);
            }
        }
    }
}
