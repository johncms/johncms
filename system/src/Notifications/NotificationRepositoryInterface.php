<?php

declare(strict_types=1);

namespace Johncms\Notifications;

/**
 * The notifications the core writes for its own events, to whichever user they concern. Unlike
 * the model, it is not limited to the notifications of the current visitor.
 */
interface NotificationRepositoryInterface
{
    /**
     * @param array<string, scalar> $fields What the template of the event reads, as plain text.
     */
    public function create(string $module, string $eventType, int $userId, int $senderId, int $entityId, array $fields): void;

    /**
     * Every notification of the event about these entities, whoever it went to.
     *
     * @param list<int> $entityIds
     */
    public function deleteForEntities(string $module, string $eventType, array $entityIds): void;
}
