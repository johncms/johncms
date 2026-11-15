<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Modules\Guestbook\Application\Services\GuestbookReplyNotifier;
use Johncms\Modules\Guestbook\Application\Uploads\GuestbookEditorUploads;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class DeleteGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private EditorUploads $uploads,
        private TransactionManagerInterface $transactions,
        private GuestbookReplyNotifier $replyNotifier,
    ) {
    }

    /** The entry goes with its pictures and the notification it answered with; the files are removed once the deletion commits. */
    public function execute(GuestbookEntry $entry): void
    {
        $this->transactions->run(function () use ($entry): void {
            $this->repository->delete($entry);
            $this->uploads->detach(GuestbookEditorUploads::OWNER_TYPE, [$entry->id]);
            $this->replyNotifier->withdrawn([$entry->id]);
        });
    }
}
