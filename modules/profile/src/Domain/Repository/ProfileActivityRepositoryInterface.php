<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Domain\Repository;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;

interface ProfileActivityRepositoryInterface
{
    /**
     * Forum posts authored by the user, newest first.
     *
     * @return LengthAwarePaginator<int, ForumMessage>
     */
    public function paginateForumMessages(int $userId, bool $includeDeleted, int $perPage): LengthAwarePaginator;

    /**
     * Forum topics authored by the user, newest first.
     *
     * @return LengthAwarePaginator<int, \Johncms\Modules\Forum\Domain\Models\ForumTopic>
     */
    public function paginateForumTopics(int $userId, bool $includeDeleted, int $perPage): LengthAwarePaginator;

    /**
     * First (oldest) message of a topic.
     */
    public function findFirstTopicMessage(int $topicId, bool $includeDeleted): ?ForumMessage;

    /**
     * Guestbook entries authored by the user, newest first.
     *
     * @return LengthAwarePaginator<int, \Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry>
     */
    public function paginateGuestbookEntries(int $userId, bool $includeAdmin, int $perPage): LengthAwarePaginator;
}
