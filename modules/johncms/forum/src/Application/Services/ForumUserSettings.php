<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\CurrentUser;

/**
 * The forum settings of the visitor, kept in the profile (users.set_forum). A guest reads the defaults.
 */
final readonly class ForumUserSettings
{
    public function __construct(
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * The posts of a topic go from the newest: its first page holds the latest ones.
     */
    public function newestFirst(): bool
    {
        if (! $this->currentUser->isValid()) {
            return false;
        }

        $settings = (array) $this->currentUser->user()->set_forum;

        return ! empty($settings['upfp']);
    }
}
