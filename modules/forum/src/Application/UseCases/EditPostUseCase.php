<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class EditPostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private User $currentUser,
    ) {
    }

    public function execute(EditPostContextDTO $context, string $messageText): void
    {
        $message = $context->message;
        $message->edit_time = time();
        $message->editor_name = $this->currentUser->name;
        $message->edit_count = ((int) $message->edit_count) + 1;
        $message->text = $messageText;

        $this->messageRepository->save($message);
    }
}
