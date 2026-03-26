<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\DTO;

final readonly class CommunityIndexResultDTO
{
    public function __construct(
        public string $title,
        public string $pageTitle,
        public string $usersCount,
        public int $adminCount,
        public int $birthDays,
    ) {
    }
}
