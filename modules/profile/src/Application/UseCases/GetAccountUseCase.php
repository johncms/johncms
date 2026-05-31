<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Modules\Profile\Application\DTO\AccountDTO;
use Johncms\Modules\Profile\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAccountUseCase
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $albumPhotoRepository,
        private MailMessageRepositoryInterface $mailRepository,
        private ContactRepositoryInterface $contactRepository,
        private User $currentUser,
    ) {
    }

    public function execute(): AccountDTO
    {
        $userId = $this->currentUser->id;

        return new AccountDTO(
            userId: $userId,
            totalPhoto: $this->albumPhotoRepository->countByUser($userId),
            guestbookCount: $this->currentUser->comm_count,
            inbox: $this->mailRepository->countInbox($userId),
            newMessages: $this->mailRepository->countNewInbox($userId),
            outbox: $this->mailRepository->countOutbox($userId),
            unreadSent: $this->mailRepository->countNewOutbox($userId),
            files: $this->mailRepository->countAttachedFiles($userId),
            contacts: $this->contactRepository->countContacts($userId),
            blockedContacts: $this->contactRepository->countBlocked($userId),
        );
    }
}
