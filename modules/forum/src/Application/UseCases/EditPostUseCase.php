<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

final readonly class EditPostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(EditPostContextDTO $context, string $messageText): void
    {
        $message = $context->message;
        $message->edit_time = time();
        $message->editor_name = $this->currentUser->user()->name;
        $message->edit_count = ((int) $message->edit_count) + 1;
        $message->text = $messageText;

        $this->messageRepository->save($message);
    }
}
