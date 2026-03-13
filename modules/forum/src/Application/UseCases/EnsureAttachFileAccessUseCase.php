<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\UploadExpiredException;
use Johncms\Modules\Forum\Domain\Exceptions\MessageNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class EnsureAttachFileAccessUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $messageId, int $page): void
    {
        $page = max(1, $page);

        if ($messageId <= 0 || ! $this->currentUser->isValid()) {
            throw new AccessDeniedException('Access denied to attach a file.');
        }

        $message = $this->messageRepository->findById($messageId);
        if ($message === null) {
            throw new MessageNotFoundException(sprintf('Message with id "%s" could not be found.', $messageId));
        }

        if ($message->user_id !== $this->currentUser->id) {
            throw new MessageNotFoundException(sprintf('Message with id "%s" could not be found.', $messageId));
        }

        if ($message->date < (time() - 3600)) {
            throw new UploadExpiredException((int) $message->topic_id, $page);
        }
    }
}
