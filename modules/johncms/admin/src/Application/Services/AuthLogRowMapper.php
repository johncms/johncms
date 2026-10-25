<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Events\AuthEventType;
use Johncms\Modules\Admin\Application\DTO\AuthLogRowDTO;
use Johncms\Modules\Admin\Domain\ValueObjects\AuthLogFilter;

/**
 * The rows of the sign-in log, as admin::auth-log/results draws them: when, what, about whom, by
 * whom and from where.
 */
final readonly class AuthLogRowMapper
{
    public function __construct(private AuthLogControlsMapper $controls)
    {
    }

    /**
     * @param list<AuthLogRowDTO> $rows
     * @return list<array<string, mixed>>
     */
    public function map(array $rows): array
    {
        return array_map($this->row(...), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(AuthLogRowDTO $row): array
    {
        return [
            'time'       => $row->createdAt,
            'label'      => $row->label,
            'tone'       => $this->tone(AuthEventType::tryFrom($row->event)),
            'user'       => $this->account($row->userId, $row->userName),
            'actor'      => $this->account($row->actorId, $row->actorName),
            'ip'         => $row->ip,
            // The address leads to everything else that came from it.
            'ip_url'     => $row->ip === '' ? null : $this->controls->url(new AuthLogFilter(search: $row->ip)),
            'user_agent' => $row->userAgent,
            'details'    => $row->details,
        ];
    }

    /**
     * @return array{id: int, name: string, url: string|null}|null
     */
    private function account(?int $id, ?string $name): ?array
    {
        if ($id === null) {
            return null;
        }

        // An account that is gone keeps its number in the log, and nothing to lead to.
        return $name === null
            ? ['id' => $id, 'name' => '#' . $id, 'url' => null]
            : ['id' => $id, 'name' => $name, 'url' => '/admin/users/' . $id];
    }

    /**
     * The color of the badge: a refusal stands out, what the staff did to an account is marked,
     * the rest is routine.
     */
    private function tone(?AuthEventType $type): string
    {
        return match ($type) {
            AuthEventType::LoginSuccess => 'success',
            AuthEventType::LoginFailed => 'danger',
            AuthEventType::RoleGranted,
            AuthEventType::RoleRevoked,
            AuthEventType::PasswordChanged,
            AuthEventType::PasswordResetCompleted => 'info',
            AuthEventType::ImpersonationStart,
            AuthEventType::ImpersonationStop,
            AuthEventType::ImpersonatedAction => 'warning',
            default => '',
        };
    }
}
