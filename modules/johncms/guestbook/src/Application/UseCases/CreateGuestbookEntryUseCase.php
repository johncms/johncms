<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Modules\Guestbook\Application\DTO\CreateGuestbookEntryDTO;
use Johncms\Modules\Guestbook\Application\Services\GuestbookReplyNotifier;
use Johncms\Modules\Guestbook\Application\Uploads\GuestbookEditorUploads;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Users\Repository\UserRepositoryInterface;

final readonly class CreateGuestbookEntryUseCase
{
    public function __construct(
        private GuestbookEntryRepositoryInterface $repository,
        private UserRepositoryInterface $userRepository,
        private CurrentUser $currentUser,
        private EditorUploads $uploads,
        private TransactionManagerInterface $transactions,
        private GuestbookReplyNotifier $replyNotifier,
    ) {
    }

    /**
     * An entry answers only one of the same guestbook. One deleted while the answer was being
     * written, or one of the other guestbook, leaves the answer without the link rather than
     * refusing it.
     *
     * @throws EditorUploadBindException when the pictures of the text cannot be taken; nothing is saved.
     */
    public function execute(CreateGuestbookEntryDTO $dto): GuestbookEntry
    {
        return $this->transactions->run(function () use ($dto): GuestbookEntry {
            // Locked till the answer is written: the entry cannot be deleted between the two.
            $answered = $dto->replyTo > 0 ? $this->repository->findLocked($dto->replyTo) : null;
            if ($answered !== null && $answered->adm !== $dto->adminClub) {
                $answered = null;
            }

            $entry = $this->repository->create(
                [
                    'adm'         => $dto->adminClub,
                    'time'        => time(),
                    'user_id'     => $this->currentUser->id(),
                    'name'        => $dto->name,
                    'text'        => $dto->text,
                    'ip'          => $dto->ip,
                    'browser'     => $dto->userAgent,
                    'reply_to_id' => $answered?->id,
                ]
            );
            $this->uploads->bind(GuestbookEditorUploads::OWNER_TYPE, $entry->id, $this->currentUser->id(), $dto->text);

            if ($answered !== null) {
                $this->replyNotifier->replied($answered, $entry);
            }

            if ($this->currentUser->isValid()) {
                $this->userRepository->registerGuestbookPost($this->currentUser->user());
            }

            return $entry;
        });
    }
}
