<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\DTO;

final readonly class CommunityIndexResultDTO
{
    public function __construct(
        public string $title,
        public string $pageTitle,
        public int $usersCount,
        public int $newUsersCount,
        public int $adminCount,
        public int $birthDays,
    ) {
    }
}
