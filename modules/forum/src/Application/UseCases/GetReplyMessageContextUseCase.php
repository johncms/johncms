<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\DTO\ReplyMessageContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;

final readonly class GetReplyMessageContextUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function execute(int $messageId): ReplyMessageContextDTO
    {
        if (
            ! $this->currentUser->isValid()
            || isset($this->currentUser->user()->ban[1])
            || isset($this->currentUser->user()->ban[11])
            || ! $this->accessChecker->allows(ForumPermissions::POST)
        ) {
            throw new ForumAccessDeniedException('Access denied to post message.');
        }

        $message = $this->messageRepository->findById($messageId);
        if ($message === null) {
            throw new ForumNotFoundException('Message not found.');
        }

        if ($message->deleted && ! $this->accessChecker->allows(ForumPermissions::DELETED_VIEW)) {
            throw new ForumNotFoundException('Message not found.');
        }

        $topic = $this->topicRepository->findById((int) $message->topic_id);
        if ($topic === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        return new ReplyMessageContextDTO($message, $topic);
    }
}
