<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\UseCases;

use Johncms\Modules\Notifications\Application\DTO\NotificationListResultDTO;
use Johncms\Modules\Notifications\Domain\Repository\NotificationRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotifications;

final readonly class GetNotificationListUseCase
{
    public function __construct(
        private SystemNotifications $systemNotifications,
        private NotificationRepositoryInterface $notificationRepository,
    ) {
    }

    public function count(): int
    {
        return $this->notificationRepository->countNotifications();
    }

    public function getPage(int $limit, int $offset): NotificationListResultDTO
    {
        $items = $this->notificationRepository->getNotifications($limit, $offset);

        $ids = $items->pluck('id')->all();
        $this->notificationRepository->markAsRead($ids);

        return new NotificationListResultDTO(
            systemNotifications: array_map(
                static fn (SystemNotification $notification): array => [
                    'name'    => $notification->name,
                    'url'     => $notification->url,
                    'counter' => $notification->countShown ? $notification->count : 0,
                ],
                $this->systemNotifications->all()
            ),
            items: $items->all(),
        );
    }
}
