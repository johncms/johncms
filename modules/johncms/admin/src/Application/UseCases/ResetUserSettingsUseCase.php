<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\UserEditRepositoryInterface;
use Johncms\Users\User;

/**
 * Puts the settings of an account back to the defaults — the way out of settings that break the
 * site for it.
 */
final readonly class ResetUserSettingsUseCase
{
    public function __construct(
        private UserEditRepositoryInterface $users,
    ) {
    }

    public function execute(User $user): void
    {
        $this->users->resetSettings($user->id);
    }
}
