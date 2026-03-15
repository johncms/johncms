<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\EditPostAccessDeniedException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class EnsureEditPostAccessUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(EditPostContextDTO $context): void
    {
        $message = $context->message;

        if ($context->effectiveRights === 3 || $context->effectiveRights >= 6) {
            if ($message->user_id !== $this->currentUser->id) {
                $author = User::query()->find($message->user_id);

                if ($author !== null && $author->rights > $context->effectiveRights) {
                    throw new EditPostAccessDeniedException(__('You cannot edit posts of higher administration'));
                }
            }

            return;
        }

        if ($message->user_id !== $this->currentUser->id) {
            throw new EditPostAccessDeniedException(__('You are trying to change another\'s post'));
        }

        $check = true;
        if ($context->section->access === 2) {
            $first = $this->messageRepository->findFirstByTopicId($message->topic_id);
            if ($first !== null && $first->user_id === $this->currentUser->id && $first->id === $message->id) {
                $check = false;
            }
        }

        if (! $check) {
            return;
        }

        $lastMessage = $this->messageRepository->findLastByTopicId($message->topic_id, false);
        if ($lastMessage === null || $lastMessage->user_id !== $this->currentUser->id) {
            throw new EditPostAccessDeniedException(__('Your message not already latest, you cannot change it'));
        }

        if ($message->date < time() - 300) {
            throw new EditPostAccessDeniedException(__('You cannot edit your posts after 5 minutes'));
        }
    }
}
