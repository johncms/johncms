<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CommunityUserRepositoryInterface
{
    public function countAdministrationUsers(): int;

    public function countBirthdayUsers(int $day, int $month): int;

    public function paginateAdministrationUsers(int $perPage): LengthAwarePaginator;

    public function paginateApprovedUsers(int $perPage): LengthAwarePaginator;

    public function paginateBirthdayUsers(int $perPage, int $day, int $month): LengthAwarePaginator;

    public function paginateUsersByLatinNameLike(int $perPage, string $searchLike): LengthAwarePaginator;

    public function getTopForumUsers(int $limit): Collection;

    public function getTopGuestbookUsers(int $limit): Collection;

    public function getTopCommentUsers(int $limit): Collection;

    public function getTopKarmaUsers(int $limit): Collection;
}
