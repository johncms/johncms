<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\AttachFileAccessDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class GetAttachFileContextUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $messageId, int $page): AttachFileAccessDTO
    {
        $page = max(1, $page);

        $message = $this->messageRepository->findById($messageId);
        if ($message === null || $message->user_id !== $this->currentUser->id) {
            throw new ForumNotFoundException(sprintf('Message with id "%s" could not be found.', $messageId));
        }

        return new AttachFileAccessDTO(
            messageId: $message->id,
            topicId: (int) $message->topic_id,
            page: $page
        );
    }
}
