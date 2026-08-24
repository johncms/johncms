<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Modules\Admin\Application\Exceptions\RoleAccessDeniedException;

/**
 * Who may change the roles of a given account.
 *
 * The same permission as the role editor, plus the hierarchy in the other direction: an
 * administrator may not take the roles of somebody standing above them. Without it, editing a
 * supervisor's roles would be a way to demote one.
 */
final readonly class EnsureUserRoleAccessUseCase
{
    public function __construct(
        private EnsureRoleManagementAccessUseCase $ensureRoleAccess,
        private RoleLevels $roleLevels,
    ) {
    }

    public function execute(int $userId, ?int $now = null): void
    {
        $this->ensureRoleAccess->execute();

        if ($this->roleLevels->highestGrantedTo($userId, $now) > $this->ensureRoleAccess->maxLevel()) {
            throw new RoleAccessDeniedException(__('You cannot change the roles of somebody who outranks you'));
        }
    }
}
