<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Application\DTO\DeleteMessageContextDTO;
use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;

final readonly class GetDeleteMessageContextUseCase
{
    public function __construct(
        private MailMessageRepositoryInterface $mailMessageRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $messageId): DeleteMessageContextDTO
    {
        $message = $this->mailMessageRepository->findById($messageId);
        if ($message === null) {
            throw new MessageNotFoundException();
        }

        // Check ownership: message must belong to current user (either as recipient or sender)
        if ($message->user_id !== $this->currentUser->id() && $message->from_id !== $this->currentUser->id()) {
            throw new MessageNotFoundException();
        }

        // Determine the other user ID for back link
        $otherUserId = $message->user_id === $this->currentUser->id()
            ? $message->from_id
            : $message->user_id;

        $backUrl = '/mail/write/' . $otherUserId;

        return new DeleteMessageContextDTO(
            messageId: $messageId,
            otherUserId: $otherUserId,
            backUrl: $backUrl,
        );
    }
}
