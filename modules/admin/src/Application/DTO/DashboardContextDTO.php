<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class DashboardContextDTO
{
    public function __construct(
        public int $activeUsersToday,
        public int $registeredUsersToday,
        public int $forumMessagesToday,
    ) {
    }
}
