<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Johncms\View\Dashboard\AttentionItem;

final readonly class DashboardContextDTO
{
    /**
     * @param list<AttentionItem> $attention What the modules ask the administrator to look at, the most pressing first.
     */
    public function __construct(
        public int $activeUsersToday,
        public int $registeredUsersToday,
        public int $forumMessagesToday,
        public array $attention,
    ) {
    }
}
