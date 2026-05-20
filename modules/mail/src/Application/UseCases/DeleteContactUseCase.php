<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Application\Exceptions\ContactNotFoundException;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class DeleteContactUseCase
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private MailMessageRepositoryInterface $mailMessageRepository,
        private MailFileService $mailFileService,
        private User $currentUser,
    ) {
    }

    public function execute(int $contactId): void
    {
        $contact = $this->contactRepository->findContact($this->currentUser->id, $contactId);
        if ($contact === null) {
            throw new ContactNotFoundException();
        }

        // Get all messages between users
        $messages = $this->mailMessageRepository->getMessagesBetween($this->currentUser->id, $contactId);

        foreach ($messages as $message) {
            $this->processMessageDeletion($message);
        }

        // Remove contact
        $this->contactRepository->removeContact($this->currentUser->id, $contactId);
    }

    private function processMessageDeletion(MailMessage $message): void
    {
        // If message is already marked for deletion by other party, or unread received by current user → hard delete
        if ($message->delete || ($message->read === 0 && $message->user_id === $this->currentUser->id)) {
            $this->deleteFileIfExists($message->file_name);
            $this->mailMessageRepository->delete($message->id);
            return;
        }

        // Otherwise: soft delete (mark as deleted for current user)
        $message->delete = $this->currentUser->id;
        $this->mailMessageRepository->save($message);
    }

    private function deleteFileIfExists(string $fileName): void
    {
        if (!empty($fileName)) {
            $this->mailFileService->deleteFile($fileName);
        }
    }
}
