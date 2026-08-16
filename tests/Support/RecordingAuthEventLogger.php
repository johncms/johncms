<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;

/**
 * Keeps the audit trail in memory, so a test can assert what was recorded without a schema, a
 * request or an identity behind it.
 */
final class RecordingAuthEventLogger implements AuthEventLoggerInterface
{
    /** @var list<array{event: string, user_id: int|null, context: array<string, mixed>, actor_id: int|null}> */
    private array $entries = [];

    public function log(
        AuthEventType|string $event,
        ?int $userId = null,
        array $context = [],
        ?int $actorId = null,
        ?int $now = null,
    ): void {
        $this->entries[] = [
            'event'    => $event instanceof AuthEventType ? $event->value : $event,
            'user_id'  => $userId,
            'context'  => $context,
            'actor_id' => $actorId,
        ];
    }

    /**
     * @return list<array{event: string, user_id: int|null, context: array<string, mixed>, actor_id: int|null}>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return list<string> The events in the order they were recorded.
     */
    public function events(): array
    {
        return array_column($this->entries, 'event');
    }
}
