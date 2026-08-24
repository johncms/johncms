<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Files\FileStore;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class DeleteGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private FileStore $files,
    ) {
    }

    public function execute(GuestbookEntry $entry): void
    {
        if (! empty($entry->attached_files)) {
            $this->files->deleteMany($entry->attached_files);
        }

        $this->repository->delete($entry);
    }
}
