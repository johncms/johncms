<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Modules\Guestbook\Application\Services\DeleteAttachedFilesService;
use Johncms\Modules\Guestbook\Domain\Enums\ClearGuestbookPeriod;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class ClearGuestbookUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private DeleteAttachedFilesService $attachedFiles,
    ) {
    }

    public function execute(bool $adminClub, ClearGuestbookPeriod $period): void
    {
        $maxAge = $period->maxAge();
        $olderThan = $maxAge !== null ? time() - $maxAge : null;

        $entries = $this->repository->getEntriesToClear($adminClub, $olderThan);
        foreach ($entries as $entry) {
            if (! empty($entry->attached_files)) {
                $this->attachedFiles->delete($entry->attached_files, $entry->id);
            }
        }

        $this->repository->deleteEntries($adminClub, $olderThan);
    }
}
