<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Users\User;

final readonly class SaveSettingsUseCase
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function execute(bool $showForumUnread): void
    {
        $this->currentUser->update([
            'notification_settings' => [
                'show_forum_unread' => $showForumUnread,
            ],
        ]);
    }
}
