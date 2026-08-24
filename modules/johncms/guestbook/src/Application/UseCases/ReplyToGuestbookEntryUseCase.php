<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class ReplyToGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @param int[] $attachedFiles
     */
    public function execute(GuestbookEntry $entry, string $reply, array $attachedFiles): void
    {
        $entry->otvet = $reply;
        $entry->admin = $this->currentUser->user()->name;
        $entry->otime = time();
        $entry->attached_files = array_merge((array) $entry->attached_files, $attachedFiles);

        $this->repository->save($entry);
    }
}
