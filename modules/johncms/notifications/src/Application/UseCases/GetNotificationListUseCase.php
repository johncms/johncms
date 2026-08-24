<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Counters;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Notifications\Application\DTO\NotificationListResultDTO;
use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;

final readonly class GetNotificationListUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private Counters $counters,
        private NotificationRepositoryInterface $notificationRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function count(): int
    {
        return $this->notificationRepository->countNotifications();
    }

    public function getPage(int $limit, int $offset): NotificationListResultDTO
    {
        $allCounters = $this->counters->notifications();
        $systemNotifications = $this->buildSystemNotifications($allCounters);

        $items = $this->notificationRepository->getNotifications($limit, $offset);

        $ids = $items->pluck('id')->all();
        $this->notificationRepository->markAsRead($ids);

        return new NotificationListResultDTO(
            systemNotifications: $systemNotifications,
            items: $items->all(),
        );
    }

    private function buildSystemNotifications(array $counters): array
    {
        $notifications = [];

        // Each of these leads to a screen of its own, and is offered to whoever may open that
        // screen — one number for all three said 'the administrator' and hid the queue of the
        // library from the moderator whose queue it is.
        if (! empty($counters['reg_total']) && $this->accessChecker->allows(CorePermissions::ADMIN_ACCESS)) {
            $notifications[] = [
                'name'    => __('Users on registration'),
                'url'     => '/admin/reg/',
                'counter' => $counters['reg_total'],
                'type'    => 'info',
            ];
        }

        if (! empty($counters['library_mod']) && $this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            $notifications[] = [
                'name'    => __('Articles on moderation'),
                'url'     => '/library/premod',
                'counter' => $counters['library_mod'],
                'type'    => 'info',
            ];
        }

        if (! empty($counters['downloads_mod']) && $this->accessChecker->allows(DownloadsPermissions::MODERATE)) {
            $notifications[] = [
                'name'    => __('Downloads on moderation'),
                'url'     => '/downloads/moderation',
                'counter' => true,
                'type'    => 'info',
            ];
        }

        if (! empty($counters['ban'])) {
            $notifications[] = [
                'name'    => __('Ban'),
                'url'     => '/profile/' . $this->currentUser->id() . '/bans',
                'counter' => 0,
                'type'    => 'warning',
            ];
        }

        if (isset($counters['forum_new']) && $counters['forum_new'] > 0) {
            $notifications[] = [
                'name'    => __('New forum posts'),
                'url'     => '/forum/?act=new',
                'counter' => $counters['forum_new'],
                'type'    => 'warning',
            ];
        }

        if (! empty($counters['new_mail'])) {
            $notifications[] = [
                'name'    => __('Mail'),
                'url'     => '/mail/incoming',
                'counter' => $counters['new_mail'],
                'type'    => 'info',
            ];
        }

        if (! empty($counters['guestbook_comments'])) {
            $notifications[] = [
                'name'    => __('Guestbook'),
                'url'     => '/profile/' . $this->currentUser->id() . '/guestbook',
                'counter' => $counters['guestbook_comments'],
                'type'    => 'info',
            ];
        }

        if (! empty($counters['new_album_comm'])) {
            $notifications[] = [
                'name'    => __('Comments'),
                'url'     => '/album/top/my-comments',
                'counter' => $counters['new_album_comm'],
                'type'    => 'info',
            ];
        }

        return $notifications;
    }
}
