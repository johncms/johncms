<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Auth\CurrentUser;

final readonly class SaveSettingsUseCase
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(bool $showForumUnread): void
    {
        $this->currentUser->user()->update([
            'notification_settings' => [
                'show_forum_unread' => $showForumUnread,
            ],
        ]);
    }
}
