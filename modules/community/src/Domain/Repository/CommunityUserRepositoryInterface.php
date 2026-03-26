<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommunityUserRepositoryInterface
{
    public function paginateAdministrationUsers(int $perPage): LengthAwarePaginator;

    public function paginateApprovedUsers(int $perPage): LengthAwarePaginator;

    public function paginateBirthdayUsers(int $perPage, int $day, int $month): LengthAwarePaginator;
}
