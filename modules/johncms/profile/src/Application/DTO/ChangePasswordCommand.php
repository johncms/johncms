<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class ChangePasswordCommand
{
    public function __construct(
        public int $profileUserId,
        public string $oldPassword,
        public string $newPassword,
        public string $confirmPassword,
    ) {
    }
}
