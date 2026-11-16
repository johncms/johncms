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
        return $this->topicUsers($topicId)->count();
    }

    public function getTopicUsers(int $topicId, int $start, int $limit): Collection
    {
        return $this->topicUsers($topicId)
            ->slice(max(0, $start), max(1, $limit))
            ->values();
    }

    public function countTopicGuests(int $topicId): int
    {
        return $this->topicGuests($topicId)->count();
    }

    public function getTopicGuests(int $topicId, int $start, int $limit): Collection
    {
        return $this->topicGuests($topicId)
            ->slice(max(0, $start), max(1, $limit))
            ->values();
    }

    /**
     * The members online in the forum, narrowed to the topic by the address each was last seen at.
     * Those online are few, so the address is matched here rather than by functions of one
     * database server.
     *
     * @return Collection<int, User>
     */
    private function topicUsers(int $topicId): Collection
    {
        return User::query()
            ->online()
            ->where('place', 'like', '/forum%')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $this->isTopicPlace((string) $user->place, $topicId))
            ->values()
            ->toBase();
    }

    /**
     * @return Collection<int, GuestSession>
     */
    private function topicGuests(int $topicId): Collection
    {
        return GuestSession::query()
            ->online()
            ->where('place', 'like', '/forum%')
            ->orderByDesc('movings')
            ->get()
            ->filter(fn (GuestSession $guest): bool => $this->isTopicPlace((string) $guest->place, $topicId))
            ->values()
            ->toBase();
    }

    /**
     * The address of a page of the topic, /forum/{sections}/{slug}-{id}/ with any query, or the
     * address of the topic before the paths, /forum?type=topic&id={id}.
     */
    private function isTopicPlace(string $place, int $topicId): bool
    {
        if (preg_match('~^/forum/.+/.+-(\d+)(?:/|\?|$)~', $place, $matches) === 1) {
            return (int) $matches[1] === $topicId;
        }

        return preg_match('~^/forum/?\?type=topic&id=(\d+)(?:&|$)~', $place, $matches) === 1
            && (int) $matches[1] === $topicId;
    }
}
