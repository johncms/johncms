<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Notifications;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The photos of the visitor with comments they have not read.
 */
final readonly class UnreadCommentsNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private AlbumPhotoRepositoryInterface $photos,
    ) {
    }

    public function notifications(): iterable
    {
        $count = $this->photos->countWithUnreadComments($this->currentUser->id());

        if ($count > 0) {
            yield new SystemNotification('new_album_comm', d__('album', 'Comments'), '/album/top/my-comments', $count);
        }
    }
}
