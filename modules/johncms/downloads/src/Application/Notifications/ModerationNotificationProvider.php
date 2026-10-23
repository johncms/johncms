<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Notifications;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The files waiting for moderation, for the moderator of the downloads.
 */
final readonly class ModerationNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private DownloadFileRepositoryInterface $files,
    ) {
    }

    public function notifications(): iterable
    {
        if (! $this->accessChecker->allows(DownloadsPermissions::MODERATE)) {
            return;
        }

        $count = $this->files->countAwaitingModeration();

        if ($count > 0) {
            yield new SystemNotification('downloads_mod', d__('downloads', 'Downloads on moderation'), '/downloads/moderation', $count);
        }
    }
}
