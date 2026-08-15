<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;

final readonly class DeleteMessageUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private MailFileService $mailFileService,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $messageId): void
    {
        $message = $this->mailMessageRepository->findById($messageId);
        if ($message === null) {
            throw new MessageNotFoundException();
        }

        // Check ownership: message must belong to current user (either as recipient or sender)
        if ($message->user_id !== $this->currentUser->id() && $message->from_id !== $this->currentUser->id()) {
            throw new MessageNotFoundException();
        }

        // System message: hard delete (no file attachment)
        if ($message->sys) {
            $this->mailMessageRepository->delete($messageId);
            return;
        }

        // Unread message received by current user: hard delete with file cleanup
        if ($message->read === 0 && $message->user_id === $this->currentUser->id()) {
            $this->deleteFileIfExists($message->file_name);
            $this->mailMessageRepository->delete($messageId);
            return;
        }

        // Already marked for deletion by other party: hard delete with file cleanup
        if ($message->delete) {
            $this->deleteFileIfExists($message->file_name);
            $this->mailMessageRepository->delete($messageId);
            return;
        }

        // Otherwise: soft delete (mark as deleted for current user)
        $message->delete = $this->currentUser->id();
        $this->mailMessageRepository->save($message);
    }

    private function deleteFileIfExists(string $fileName): void
    {
        if (!empty($fileName)) {
            $this->mailFileService->deleteFile($fileName);
        }
    }
}
