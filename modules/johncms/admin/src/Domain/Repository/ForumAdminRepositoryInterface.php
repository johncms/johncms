<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

interface ForumAdminRepositoryInterface
{
    /**
     * Счётчики для дашборда управления форумом.
     *
     * @return array{
     *     total_cat: int, total_sub: int, total_thm: int, total_thm_del: int,
     *     total_msg: int, total_msg_del: int, total_files: int, total_votes: int
     * }
     */
    public function dashboardCounters(): array;
}
