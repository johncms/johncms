<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class DownloadFileUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private MailFileService $mailFileService,
        private User $currentUser,
    ) {
    }

    public function execute(int $messageId): string
    {
        $message = $this->mailMessageRepository->findById($messageId);

        if (
            $message === null
            || ($message->user_id !== $this->currentUser->id && $message->from_id !== $this->currentUser->id)
            || empty($message->file_name)
            || $message->delete === $this->currentUser->id
        ) {
            throw new MessageNotFoundException();
        }

        if (! $this->mailFileService->fileExists($message->file_name)) {
            throw new MessageNotFoundException();
        }

        $this->mailMessageRepository->incrementDownloadCount($messageId);

        return '/upload/mail/' . $message->file_name;
    }
}
