<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\Uploads\GuestbookEditorUploads;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;

final readonly class ReplyToGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private CurrentUser $currentUser,
        private EditorUploads $uploads,
        private TransactionManagerInterface $transactions,
    ) {
    }

    /**
     * @throws GuestbookEntryNotFoundException when the entry was deleted meanwhile.
     * @throws EditorUploadBindException when the pictures of the reply cannot be taken; nothing is saved.
     */
    public function execute(GuestbookEntry $entry, string $reply): void
    {
        $this->transactions->run(function () use ($entry, $reply): void {
            $locked = $this->repository->findLocked($entry->id) ?? throw new GuestbookEntryNotFoundException();
            $locked->otvet = $reply;
            $locked->admin = $this->currentUser->user()->name;
            $locked->otime = time();
            $this->repository->save($locked);

            // The entry and its reply are one record for the pictures: those of the entry stay.
            $this->uploads->bind(
                GuestbookEditorUploads::OWNER_TYPE,
                $locked->id,
                $this->currentUser->id(),
                (string) $locked->text . $reply
            );
        });
    }
}
