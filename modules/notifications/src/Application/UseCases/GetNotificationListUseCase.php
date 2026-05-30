<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Counters;
use Johncms\Modules\Notifications\Application\DTO\NotificationListResultDTO;
use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;
use Johncms\Users\User;

final readonly class GetNotificationListUseCase
{
    public function __construct(
        private Counters $counters,
        private NotificationRepositoryInterface $notificationRepository,
        private User $currentUser,
    ) {
    }

    public function execute(int $page, int $perPage): NotificationListResultDTO
    {
        $allCounters = $this->counters->notifications();
        $systemNotifications = $this->buildSystemNotifications($allCounters);

        $paginator = $this->notificationRepository->getPaginated($page, $perPage);

        $ids = array_column($paginator->items(), 'id');
        $this->notificationRepository->markAsRead($ids);

        return new NotificationListResultDTO(
            systemNotifications: $systemNotifications,
            items: $paginator->items(),
            total: $paginator->total(),
            pagination: (string) $paginator->render(),
        );
    }

    private function buildSystemNotifications(array $counters): array
    {
        $notifications = [];

        if ($this->currentUser->rights >= 7) {
            if (! empty($counters['reg_total'])) {
                $notifications[] = [
                    'name'    => __('Users on registration'),
                    'url'     => '/admin/reg/',
                    'counter' => $counters['reg_total'],
                    'type'    => 'info',
                ];
            }
            if (! empty($counters['library_mod'])) {
                $notifications[] = [
                    'name'    => __('Articles on moderation'),
                    'url'     => '/library/premod',
                    'counter' => $counters['library_mod'],
                    'type'    => 'info',
                ];
            }
            if (! empty($counters['downloads_mod'])) {
                $notifications[] = [
                    'name'    => __('Downloads on moderation'),
                    'url'     => '/downloads/moderation',
                    'counter' => true,
                    'type'    => 'info',
                ];
            }
        }

        if (! empty($counters['ban'])) {
            $notifications[] = [
                'name'    => __('Ban'),
                'url'     => '/profile/?act=ban',
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
                'url'     => '/profile/?act=guestbook&user=' . $this->currentUser->id,
                'counter' => $counters['guestbook_comments'],
                'type'    => 'info',
            ];
        }

        if (! empty($counters['new_album_comm'])) {
            $notifications[] = [
                'name'    => __('Comments'),
                'url'     => '/album/?act=top&mod=my_new_comm',
                'counter' => $counters['new_album_comm'],
                'type'    => 'info',
            ];
        }

        return $notifications;
    }
}
