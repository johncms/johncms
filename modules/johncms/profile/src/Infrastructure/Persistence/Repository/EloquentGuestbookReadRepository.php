<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Profile\Application\Comments\ProfileGuestbookCommentSubject;
use Johncms\Modules\Profile\Domain\Repository\GuestbookReadRepositoryInterface;

final readonly class EloquentGuestbookReadRepository implements GuestbookReadRepositoryInterface
{
    private const string TABLE = 'profile__guestbook_reads';

    public function markRead(int $ownerId): void
    {
        $last = (int) Capsule::table('comments')
            ->where('subject_type', ProfileGuestbookCommentSubject::TYPE)
            ->where('subject_id', $ownerId)
            ->max('id');

        Capsule::table(self::TABLE)->updateOrInsert(['user_id' => $ownerId], ['seen_comment_id' => $last]);
    }

    public function countUnread(int $ownerId): int
    {
        $seen = (int) Capsule::table(self::TABLE)->where('user_id', $ownerId)->value('seen_comment_id');

        return Capsule::table('comments')
            ->where('subject_type', ProfileGuestbookCommentSubject::TYPE)
            ->where('subject_id', $ownerId)
            ->where('user_id', '!=', $ownerId)
            ->where('id', '>', $seen)
            ->count();
    }
}
