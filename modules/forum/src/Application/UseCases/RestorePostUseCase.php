<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

final readonly class RestorePostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumFileRepositoryInterface $fileRepository,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(EditPostContextDTO $context): void
    {
        $message = $context->message;

        $author = User::query()->find($message->user_id);
        if ($author !== null) {
            $author->update(['postforum' => $author->postforum + 1]);
        }

        $this->messageRepository->restoreById($message->id, $this->currentUser->name);
        $this->fileRepository->restoreByPostId($message->id);
        $this->tools->recountForumTopic($message->topic_id);
    }
}
