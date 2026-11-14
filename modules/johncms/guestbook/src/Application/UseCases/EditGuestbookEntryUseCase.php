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

final readonly class EditGuestbookEntryUseCase
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
     * @throws EditorUploadBindException when the pictures of the text cannot be taken; nothing is saved.
     */
    public function execute(GuestbookEntry $entry, string $text): void
    {
        $this->transactions->run(function () use ($entry, $text): void {
            $locked = $this->repository->findLocked($entry->id) ?? throw new GuestbookEntryNotFoundException();
            $locked->text = $text;
            $locked->edit_who = $this->currentUser->user()->name;
            $locked->edit_time = time();
            $locked->edit_count = $locked->edit_count + 1;
            $this->repository->save($locked);

            // The reply shares the pictures of the entry: what it shows is kept too.
            $this->uploads->bind(
                GuestbookEditorUploads::OWNER_TYPE,
                $locked->id,
                $this->currentUser->id(),
                $text . (string) $locked->otvet
            );
        });
    }
}
