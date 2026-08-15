<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class EditGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @param int[] $attachedFiles
     */
    public function execute(GuestbookEntry $entry, string $text, array $attachedFiles): void
    {
        $entry->text = $text;
        $entry->edit_who = $this->currentUser->user()->name;
        $entry->edit_time = time();
        $entry->edit_count = $entry->edit_count + 1;
        $entry->attached_files = array_merge((array) $entry->attached_files, $attachedFiles);

        $this->repository->save($entry);
    }
}
