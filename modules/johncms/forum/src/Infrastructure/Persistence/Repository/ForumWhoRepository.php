<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

final readonly class ForumWhoRepository implements ForumWhoRepositoryInterface
{
    public function countForumUsers(): int
    {
        return $this->onlineUsers()
            ->where('place', 'like', '/forum%')
            ->count();
    }

    public function getForumUsers(int $start, int $limit): Collection
    {
        /** @var Collection<int, User> $users */
        $users = $this->onlineUsers()
            ->where('place', 'like', '/forum%')
            ->orderBy('name')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get();

        return $users;
    }

    public function countForumGuests(): int
    {
        return $this->onlineGuests()
            ->where('place', 'like', '/forum%')
            ->count();
    }

    public function getForumGuests(int $start, int $limit): Collection
    {
        /** @var Collection<int, GuestSession> $guests */
        $guests = $this->onlineGuests()
            ->where('place', 'like', '/forum%')
            ->orderByDesc('movings')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get();

        return $guests;
    }

    public function countTopicUsers(int $topicId): int
    {
        return $this->inTopic($this->onlineUsers(), $topicId)->count();
    }

    public function getTopicUsers(int $topicId, int $start, int $limit): Collection
    {
        /** @var Collection<int, User> $users */
        $users = $this->inTopic($this->onlineUsers(), $topicId)
            ->orderBy('name')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get()
            ->toBase();

        return $users;
    }

    public function countTopicGuests(int $topicId): int
    {
        return $this->inTopic($this->onlineGuests(), $topicId)->count();
    }

    public function getTopicGuests(int $topicId, int $start, int $limit): Collection
    {
        /** @var Collection<int, GuestSession> $guests */
        $guests = $this->inTopic($this->onlineGuests(), $topicId)
            ->orderByDesc('movings')
            ->offset(max(0, $start))
            ->limit(max(1, $limit))
            ->get()
            ->toBase();

        return $guests;
    }

    /**
     * @return Builder<User>
     */
    private function onlineUsers(): Builder
    {
        return User::query()->online();
    }

    /**
     * @return Builder<GuestSession>
     */
    private function onlineGuests(): Builder
    {
        return GuestSession::query()->online();
    }

    /**
     * Narrows the visitors to those last seen on a page of the topic, by plain LIKE that every
     * database runs. The topic is the last segment of its address, /forum/{sections}/{slug}-{id}/,
     * so the patterns end there: a section whose name ends in the same number does not match.
     * The address of the topic before the paths, /forum?type=topic&id={id}, is matched as well.
     *
     * @template TBuilder of Builder
     * @param TBuilder $query
     * @return TBuilder
     */
    private function inTopic(Builder $query, int $topicId): Builder
    {
        $id = (string) $topicId;

        return $query->where(static function (Builder $places) use ($id): void {
            foreach (['/forum/%-' . $id, '/forum/%-' . $id . '/', '/forum/%-' . $id . '/?%', '/forum/%-' . $id . '?%'] as $pattern) {
                $places->orWhere('place', 'like', $pattern);
            }
            foreach (['/forum?type=topic&id=' . $id, '/forum/?type=topic&id=' . $id] as $legacy) {
                $places->orWhere('place', '=', $legacy)->orWhere('place', 'like', $legacy . '&%');
            }
        });
    }
}
