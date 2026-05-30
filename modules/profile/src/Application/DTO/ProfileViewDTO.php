<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class ProfileViewDTO
{
    /**
     * @param array<string, mixed> $user
     * @param string[] $notifications
     * @param array{ban: int} $counters
     * @param array<int, array{url: string, name: string}> $buttons
     */
    public function __construct(
        public string $title,
        public array $user,
        public bool $showIp,
        public bool $canWrite,
        public bool $blocked,
        public array $notifications,
        public bool $activeBan,
        public string $activeBanReason,
        public array $counters,
        public array $buttons,
    ) {
    }
}
