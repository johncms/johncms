<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class EnsureEditPostAccessUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
        private CurrentUser $identity,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(EditPostContextDTO $context): void
    {
        $message = $context->message;

        if ($context->canModerate) {
            // A moderator does not touch the posts of somebody standing above them; the roles say
            // who stands where.
            if ($message->user_id !== $this->currentUser->id) {
                $authorLevel = $this->roleLevels->highestGrantedTo((int) $message->user_id);

                if ($authorLevel > $this->roleLevels->highest($this->identity->identity())) {
                    throw new ForumAccessDeniedException(__('You cannot edit posts of higher administration'));
                }
            }

            return;
        }

        if ($message->user_id !== $this->currentUser->id) {
            throw new ForumAccessDeniedException(__('You are trying to change another\'s post'));
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
            throw new ForumAccessDeniedException(__('Your message not already latest, you cannot change it'));
        }

        if ($message->date < time() - 300) {
            throw new ForumAccessDeniedException(__('You cannot edit your posts after 5 minutes'));
        }
    }
}
