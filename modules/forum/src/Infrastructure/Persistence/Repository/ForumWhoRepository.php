<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

final readonly class ForumWhoRepository implements ForumWhoRepositoryInterface
{
    public function countForumUsers(): int
    {
        return User::query()
            ->online()
            ->where('place', 'like', '/forum%')
            ->count();
    }

    public function getForumUsers(int $start, int $limit): Collection
    {
        return User::query()
            ->online()
            ->where('place', 'like', '/forum%')
            ->orderBy('name')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get();
    }

    public function countForumGuests(): int
    {
        return GuestSession::query()
            ->online()
            ->where('place', 'like', '/forum%')
            ->count();
    }

    public function getForumGuests(int $start, int $limit): Collection
    {
        return GuestSession::query()
            ->online()
            ->where('place', 'like', '/forum%')
            ->orderByDesc('movings')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get();
    }

    public function countTopicUsers(int $topicId): int
    {
        $placeIdExpression = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`users`.`place`, 'id=', -1), '&', 1) AS UNSIGNED)";

        return User::query()
            ->online()
            ->where('users.place', 'regexp', '^/forum?(.*)id=([0-9]+)')
            ->where(static function ($query) use ($topicId, $placeIdExpression): void {
                $query->whereRaw($placeIdExpression . ' = ?', [$topicId])
                    ->orWhereExists(static function ($subQuery) use ($topicId, $placeIdExpression): void {
                        $subQuery->selectRaw('1')
                            ->from('forum_messages as frm')
                            ->whereRaw('frm.id = ' . $placeIdExpression)
                            ->where('frm.topic_id', $topicId);
                    });
            })
            ->count();
    }

    public function getTopicUsers(int $topicId, int $start, int $limit): Collection
    {
        $placeIdExpression = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`users`.`place`, 'id=', -1), '&', 1) AS UNSIGNED)";

        return User::query()
            ->online()
            ->where('users.place', 'regexp', '^/forum?(.*)id=([0-9]+)')
            ->where(static function ($query) use ($topicId, $placeIdExpression): void {
                $query->whereRaw($placeIdExpression . ' = ?', [$topicId])
                    ->orWhereExists(static function ($subQuery) use ($topicId, $placeIdExpression): void {
                        $subQuery->selectRaw('1')
                            ->from('forum_messages as frm')
                            ->whereRaw('frm.id = ' . $placeIdExpression)
                            ->where('frm.topic_id', $topicId);
                    });
            })
            ->orderBy('users.name')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get();
    }

    public function countTopicGuests(int $topicId): int
    {
        return GuestSession::query()
            ->online()
            ->where('place', 'like', '/forum?type=topic&id=' . $topicId . '%')
            ->count();
    }

    public function getTopicGuests(int $topicId, int $start, int $limit): Collection
    {
        return GuestSession::query()
            ->online()
            ->where('place', 'like', '/forum?type=topic&id=' . $topicId . '%')
            ->orderByDesc('movings')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get();
    }
}
