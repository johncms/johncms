<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Repository;

use Illuminate\Support\Collection;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

interface ForumWhoRepositoryInterface
{
    public function countForumUsers(): int;

    /**
     * @return Collection<int, User>
     */
    public function getForumUsers(int $start, int $limit): Collection;

    public function countForumGuests(): int;

    /**
     * @return Collection<int, GuestSession>
     */
    public function getForumGuests(int $start, int $limit): Collection;

    public function countTopicUsers(int $topicId): int;

    /**
     * @return Collection<int, User>
     */
    public function getTopicUsers(int $topicId, int $start, int $limit): Collection;

    public function countTopicGuests(int $topicId): int;

    /**
     * @return Collection<int, GuestSession>
     */
    public function getTopicGuests(int $topicId, int $start, int $limit): Collection;
}
