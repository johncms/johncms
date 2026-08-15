<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\AttachFileAccessDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\UploadExpiredException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

final readonly class GetAttachFileContextUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(int $messageId, int $page): AttachFileAccessDTO
    {
        $page = max(1, $page);

        if ($messageId <= 0 || ! $this->currentUser->isValid()) {
            throw new ForumAccessDeniedException('Access denied to attach a file.');
        }

        $message = $this->messageRepository->findById($messageId);
        if ($message === null || $message->user_id !== $this->currentUser->id()) {
            throw new ForumNotFoundException(sprintf('Message with id "%s" could not be found.', $messageId));
        }

        if ($message->date < (time() - 3600)) {
            throw new UploadExpiredException($message->topic_id, $page);
        }

        return new AttachFileAccessDTO(
            messageId: $message->id,
            topicId:   $message->topic_id,
            page:      $page
        );
    }
}
