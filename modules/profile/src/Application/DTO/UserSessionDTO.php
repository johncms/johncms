<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class UserSessionDTO
{
    public function __construct(
        public int $id,
        public string $ip,
        public string $userAgent,
        public int $lastUsedAt,
        public int $createdAt,
        public int $expiresAt,
        public bool $remember,
        /** Whether this is the device the page is being viewed from. */
        public bool $isCurrent,
    ) {
    }
}
