<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Users\User;

interface CommunityUserRepositoryInterface
{
    public function countAdministrationUsers(): int;

    public function countBirthdayUsers(int $day, int $month): int;

    public function countApprovedAdministrationUsers(): int;

    /**
     * @return Collection<int, User>
     */
    public function getApprovedAdministrationUsers(int $limit, int $offset): Collection;

    public function countApprovedUsers(): int;

    /**
     * @return Collection<int, User>
     */
    public function getApprovedUsers(int $limit, int $offset): Collection;

    public function countApprovedBirthdayUsers(int $day, int $month): int;

    /**
     * @return Collection<int, User>
     */
    public function getApprovedBirthdayUsers(int $limit, int $offset, int $day, int $month): Collection;

    public function countUsersByLatinNameLike(string $searchLike): int;

    /**
     * @return Collection<int, User>
     */
    public function getUsersByLatinNameLike(int $limit, int $offset, string $searchLike): Collection;

    public function getTopForumUsers(int $limit): Collection;

    public function getTopGuestbookUsers(int $limit): Collection;

    public function getTopCommentUsers(int $limit): Collection;

    public function getTopKarmaUsers(int $limit): Collection;
}
