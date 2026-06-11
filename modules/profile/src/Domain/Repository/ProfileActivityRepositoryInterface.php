<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;

interface ProfileActivityRepositoryInterface
{
    /**
     * Count forum posts authored by the user.
     */
    public function countForumMessages(int $userId, bool $includeDeleted): int;

    /**
     * A page of forum posts authored by the user, newest first.
     *
     * @return Collection<int, ForumMessage>
     */
    public function getForumMessages(int $userId, bool $includeDeleted, int $limit, int $offset): Collection;

    /**
     * Count forum topics authored by the user.
     */
    public function countForumTopics(int $userId, bool $includeDeleted): int;

    /**
     * A page of forum topics authored by the user, newest first.
     *
     * @return Collection<int, ForumTopic>
     */
    public function getForumTopics(int $userId, bool $includeDeleted, int $limit, int $offset): Collection;

    /**
     * First (oldest) message of a topic.
     */
    public function findFirstTopicMessage(int $topicId, bool $includeDeleted): ?ForumMessage;

    /**
     * Count guestbook entries authored by the user.
     */
    public function countGuestbookEntries(int $userId, bool $includeAdmin): int;

    /**
     * A page of guestbook entries authored by the user, newest first.
     *
     * @return Collection<int, GuestbookEntry>
     */
    public function getGuestbookEntries(int $userId, bool $includeAdmin, int $limit, int $offset): Collection;
}
