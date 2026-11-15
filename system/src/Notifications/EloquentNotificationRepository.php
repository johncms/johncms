<?php

declare(strict_types=1);

namespace Johncms\Notifications;

final readonly class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function create(string $module, string $eventType, int $userId, int $senderId, int $entityId, array $fields): void
    {
        Notification::query()->create([
            'module'     => $module,
            'event_type' => $eventType,
            'user_id'    => $userId,
            'sender_id'  => $senderId,
            'entity_id'  => $entityId,
            'fields'     => $fields,
        ]);
    }

    public function deleteForEntities(string $module, string $eventType, array $entityIds): void
    {
        foreach (array_chunk($entityIds, 500) as $chunk) {
            Notification::query()
                ->withoutGlobalScope('access')
                ->where('module', $module)
                ->where('event_type', $eventType)
                ->whereIn('entity_id', $chunk)
                ->delete();
        }
    }
}
