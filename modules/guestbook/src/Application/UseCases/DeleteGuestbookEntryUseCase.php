<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\Services\DeleteAttachedFilesService;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class DeleteGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private DeleteAttachedFilesService $attachedFiles,
    ) {
    }

    public function execute(GuestbookEntry $entry): void
    {
        if (! empty($entry->attached_files)) {
            $this->attachedFiles->delete($entry->attached_files, $entry->id);
        }

        $this->repository->delete($entry);
    }
}
