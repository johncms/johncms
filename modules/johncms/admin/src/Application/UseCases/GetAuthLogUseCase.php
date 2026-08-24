<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Events\AuthEvent;
use Johncms\Modules\Admin\Application\DTO\AuthLogRowDTO;
use Johncms\Modules\Admin\Domain\Repository\AuthLogRepositoryInterface;

/**
 * The sign-in log, as the admin panel reads it.
 */
final readonly class GetAuthLogUseCase
{
    public function __construct(private AuthLogRepositoryInterface $repository)
    {
    }

    public function count(?int $userId = null, ?string $event = null): int
    {
        return $this->repository->count($userId, $event);
    }

    /**
     * @return list<AuthLogRowDTO>
     */
    public function getPage(int $limit, int $offset, ?int $userId = null, ?string $event = null): array
    {
        $entries = $this->repository->get($limit, $offset, $userId, $event);
        $names = $this->repository->namesOf($this->idsOf($entries->all()));

        return array_map(
            static fn (AuthEvent $entry): AuthLogRowDTO => new AuthLogRowDTO(
                id: $entry->id,
                createdAt: $entry->created_at,
                event: $entry->event,
                // An unknown key is shown as it is: a module may log its own events, and a
                // deleted module must not turn the log into an error page.
                label: $entry->type()?->label() ?? $entry->event,
                userId: $entry->user_id,
                userName: $entry->user_id === null ? null : ($names[$entry->user_id] ?? null),
                actorId: $entry->actor_id,
                actorName: $entry->actor_id === null ? null : ($names[$entry->actor_id] ?? null),
                ip: $entry->ip,
                userAgent: $entry->user_agent,
                details: self::flatten($entry->context ?? []),
            ),
            $entries->all()
        );
    }

    /**
     * @param list<AuthEvent> $entries
     *
     * @return list<int>
     */
    private function idsOf(array $entries): array
    {
        $ids = [];

        foreach ($entries as $entry) {
            foreach ([$entry->user_id, $entry->actor_id] as $id) {
                if ($id !== null) {
                    $ids[$id] = $id;
                }
            }
        }

        return array_values($ids);
    }

    /**
     * The context is free-form, so it is shown as it was written rather than being interpreted.
     *
     * @param array<string, mixed> $context
     *
     * @return list<string>
     */
    private static function flatten(array $context): array
    {
        $lines = [];

        foreach ($context as $key => $value) {
            $lines[] = $key . ': ' . (is_scalar($value) ? (string) $value : json_encode($value));
        }

        return $lines;
    }
}
