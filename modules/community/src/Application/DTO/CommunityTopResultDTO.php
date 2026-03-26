<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\DTO;

use Johncms\Users\User;

final readonly class CommunityTopResultDTO
{
    /**
     * @param array<int, array{name: string, url: string, active: bool}> $tabs
     * @param array<int, User> $list
     */
    public function __construct(
        public string $title,
        public string $pageTitle,
        public string $activeTab,
        public array $tabs,
        public array $list,
        public int $total,
    ) {
    }
}
