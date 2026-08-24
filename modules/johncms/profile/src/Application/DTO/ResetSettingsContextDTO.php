<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class ResetSettingsContextDTO
{
    public function __construct(
        public int $profileUserId,
        public string $profileUserName,
    ) {
    }
}
