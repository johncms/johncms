<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class ClearConversationUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private MailFileService $mailFileService,
        private User $currentUser,
    ) {
    }

    public function execute(int $contactId): void
    {
        $contact = User::query()->find($contactId);
        if ($contact === null) {
            throw new UserNotFoundException();
        }

        $messages = $this->mailMessageRepository->getMessagesBetween($this->currentUser->id, $contactId);

        foreach ($messages as $message) {
            $this->processMessageDeletion($message);
        }
    }

    private function processMessageDeletion(MailMessage $message): void
    {
        // Hard delete if already marked for deletion by the other party,
        // or if it is still unread and was authored by the current user.
        if ($message->delete || (! $message->read && $message->user_id === $this->currentUser->id)) {
            $this->deleteFileIfExists($message->file_name);
            $this->mailMessageRepository->delete($message->id);
            return;
        }

        // Otherwise: soft delete (mark as deleted for the current user).
        $message->delete = $this->currentUser->id;
        $this->mailMessageRepository->save($message);
    }

    private function deleteFileIfExists(string $fileName): void
    {
        if (! empty($fileName)) {
            $this->mailFileService->deleteFile($fileName);
        }
    }
}
