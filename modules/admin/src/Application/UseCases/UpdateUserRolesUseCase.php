<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Events\AuthEventLoggerInterface;
use Johncms\Auth\Events\AuthEventType;

final readonly class UpdateUserRolesUseCase
{
    public function __construct(
        private RoleRepositoryInterface $roles,
        private AuthEventLoggerInterface $eventLogger,
    ) {
    }

    /**
     * @param array<int, int|null> $selected    The roles that were ticked: id => when the grant
     *                                          runs out, null when it never does.
     * @param int                  $viewerLevel What the visitor may reach; anything above it is
     *                                          left exactly as it was.
     */
    public function execute(
        int $userId,
        array $selected,
        int $viewerLevel,
        ?int $grantedBy = null,
        ?int $now = null,
    ): void {
        $now ??= time();
        $existing = $this->roles->grantsFor($userId);

        /** @var Role $role */
        foreach ($this->roles->all() as $role) {
            // Neither is ever granted: the guest role belongs to visitors without an account, and
            // the default one applies without a row.
            if ($role->is_guest || $role->is_default) {
                continue;
            }

            // A role the form never showed must not be taken away by a request that omits it.
            if ($role->level > $viewerLevel) {
                continue;
            }

            $wasGranted = array_key_exists($role->id, $existing);
            $isSelected = array_key_exists($role->id, $selected);

            if ($isSelected && (! $wasGranted || $existing[$role->id] !== $selected[$role->id])) {
                $this->roles->grant($userId, $role->id, $grantedBy, $now, $selected[$role->id]);
                $this->logChange(AuthEventType::RoleGranted, $userId, $role, $selected[$role->id], $now);
            } elseif (! $isSelected && $wasGranted) {
                $this->roles->revoke($userId, $role->id);
                $this->logChange(AuthEventType::RoleRevoked, $userId, $role, null, $now);
            }
        }
    }

    /**
     * The role is recorded by slug rather than by id: the log is read long after the fact, and a
     * role that has been deleted since must still be readable in it.
     */
    private function logChange(
        AuthEventType $event,
        int $userId,
        Role $role,
        ?int $expiresAt,
        int $now,
    ): void {
        $context = ['role' => $role->slug];

        if ($expiresAt !== null) {
            $context['expires_at'] = $expiresAt;
        }

        $this->eventLogger->log($event, $userId, $context, now: $now);
    }
}
