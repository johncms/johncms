<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Persistence\Repository;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Query\Builder;
use Johncms\Modules\Admin\Domain\Repository\UserDeletionRepositoryInterface;
use Johncms\Users\User;

final class EloquentUserDeletionRepository implements UserDeletionRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function countComments(int $id): int
    {
        return $this->countByUser('cms_library_comments', $id)
            + $this->countByUser('download__comments', $id)
            + $this->countByUser('cms_users_guestbook', $id)
            + $this->countByUser('cms_album_comments', $id);
    }

    public function countForumTopics(int $id): int
    {
        return $this->notDeleted(Capsule::table('forum_topic')->where('user_id', $id))->count();
    }

    public function countForumPosts(int $id): int
    {
        return $this->notDeleted(Capsule::table('forum_messages')->where('user_id', $id))->count();
    }

    private function countByUser(string $table, int $id): int
    {
        return Capsule::table($table)->where('user_id', $id)->count();
    }

    private function notDeleted(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('deleted', '!=', 1)->orWhereNull('deleted');
        });
    }
}
