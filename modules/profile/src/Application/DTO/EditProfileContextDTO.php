<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

use Johncms\Users\User;

final readonly class EditProfileContextDTO
{
    public function __construct(
        public User $profileUser,
        public bool $isSelf,
        public bool $canEditAdminFields,
        public bool $canResetSettings,
    ) {
    }
}
