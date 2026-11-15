<?php

declare(strict_types=1);

namespace Johncms\Comments\Infrastructure;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Comments\Domain\Comment;
use Johncms\Comments\Domain\CommentRepositoryInterface;

final readonly class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function count(string $subjectType, int $subjectId): int
    {
        return $this->ofSubject($subjectType, $subjectId)->count();
    }

    public function countBySubjects(string $subjectType, array $subjectIds): array
    {
        $counts = Comment::query()
            ->where('subject_type', $subjectType)
            ->whereIn('subject_id', $subjectIds)
            ->groupBy('subject_id')
            ->selectRaw('subject_id, COUNT(*) AS aggregate')
            ->pluck('aggregate', 'subject_id')
            ->all();

        $result = [];
        foreach ($counts as $subjectId => $count) {
            $result[(int) $subjectId] = (int) $count;
        }

        return $result;
    }

    public function getPage(string $subjectType, int $subjectId, int $limit, int $offset): array
    {
        /** @var list<Comment> $comments */
        $comments = $this->ofSubject($subjectType, $subjectId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->all();

        return $comments;
    }

    public function countBefore(Comment $comment): int
    {
        return $this->ofSubject($comment->subject_type, $comment->subject_id)
            ->where(static function (Builder $query) use ($comment): void {
                $query->where('created_at', '<', $comment->created_at)
                    ->orWhere(static function (Builder $query) use ($comment): void {
                        $query->where('created_at', $comment->created_at)->where('id', '<', $comment->id);
                    });
            })
            ->count();
    }

    public function find(string $subjectType, int $subjectId, int $id): ?Comment
    {
        /** @var Comment|null $comment */
        $comment = $this->ofSubject($subjectType, $subjectId)->where('id', $id)->first();

        return $comment;
    }

    public function getByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var array<int, Comment> $comments */
        $comments = Comment::query()->whereIn('id', $ids)->get()->keyBy('id')->all();

        return $comments;
    }

    public function findLatestOfUser(string $subjectType, int $userId): ?Comment
    {
        /** @var Comment|null $comment */
        $comment = Comment::query()
            ->where('subject_type', $subjectType)
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->first();

        return $comment;
    }

    public function getLatest(string $subjectType, array $excludedSubjectIds, int $limit, int $offset): array
    {
        /** @var list<Comment> $comments */
        $comments = $this->ofType($subjectType, $excludedSubjectIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->all();

        return $comments;
    }

    public function countOfType(string $subjectType, array $excludedSubjectIds = []): int
    {
        return $this->ofType($subjectType, $excludedSubjectIds)->count();
    }

    /**
     * @param list<int> $excludedSubjectIds
     * @return Builder<Comment>
     */
    private function ofType(string $subjectType, array $excludedSubjectIds): Builder
    {
        $query = Comment::query()->where('subject_type', $subjectType);
        foreach (array_chunk($excludedSubjectIds, 1000) as $chunk) {
            $query->whereNotIn('subject_id', $chunk);
        }

        return $query;
    }

    public function countByAuthors(string $subjectType, int $subjectId, array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $counts = [];
        $rows = $this->ofSubject($subjectType, $subjectId)
            ->toBase()
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) AS total')
            ->get();
        foreach ($rows as $row) {
            $counts[(int) $row->user_id] = (int) $row->total;
        }

        return $counts;
    }

    public function create(array $attributes): Comment
    {
        return Comment::query()->create($attributes);
    }

    public function update(Comment $comment, array $attributes): void
    {
        $comment->update($attributes);
    }

    public function getLocked(string $subjectType, int $subjectId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        /** @var Builder<Comment> $query */
        $query = $this->ofSubject($subjectType, $subjectId)->whereIn('id', $ids);

        return $this->authorsById($query);
    }

    public function getOfAuthor(string $subjectType, int $subjectId, int $userId): array
    {
        return $this->authorsById($this->ofSubject($subjectType, $subjectId)->where('user_id', $userId));
    }

    public function getOfSubjects(string $subjectType, array $subjectIds): array
    {
        if ($subjectIds === []) {
            return [];
        }

        /** @var Builder<Comment> $query */
        $query = Comment::query()->where('subject_type', $subjectType)->whereIn('subject_id', $subjectIds);

        return $this->authorsById($query);
    }

    public function getOfUser(int $userId): array
    {
        $comments = [];
        foreach (Comment::query()->where('user_id', $userId)->toBase()->lockForUpdate()->get(['id', 'subject_type', 'subject_id']) as $row) {
            $comments[] = ['id' => (int) $row->id, 'subject_type' => (string) $row->subject_type, 'subject_id' => (int) $row->subject_id];
        }

        return $comments;
    }

    public function countOfUser(int $userId): int
    {
        return Comment::query()->where('user_id', $userId)->count();
    }

    public function delete(array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            Comment::query()->whereIn('id', $chunk)->delete();
        }
    }

    public function unlinkAnswers(array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            Comment::query()->whereIn('reply_to_id', $chunk)->update(['reply_to_id' => null]);
        }
    }

    public function changeUserCommentCount(int $userId, int $delta): void
    {
        if ($userId === 0 || $delta === 0) {
            return;
        }

        $users = Capsule::table('users')->where('id', $userId);
        if ($delta > 0) {
            $users->increment('komm', $delta);

            return;
        }

        // Spelled out rather than GREATEST(): the column is unsigned, and SQLite has no GREATEST.
        $users->update(['komm' => Capsule::connection()->raw('CASE WHEN komm > ' . -$delta . ' THEN komm - ' . -$delta . ' ELSE 0 END')]);
    }

    public function touchLastPost(int $userId, int $time): void
    {
        Capsule::table('users')->where('id', $userId)->update(['lastpost' => $time]);
    }

    /**
     * @return Builder<Comment>
     */
    private function ofSubject(string $subjectType, int $subjectId): Builder
    {
        return Comment::query()->where('subject_type', $subjectType)->where('subject_id', $subjectId);
    }

    /**
     * The rows are locked: they are read to be deleted.
     *
     * @param Builder<Comment> $query
     * @return array<int, int>
     */
    private function authorsById(Builder $query): array
    {
        $authors = [];
        foreach ($query->toBase()->lockForUpdate()->get(['id', 'user_id']) as $row) {
            $authors[(int) $row->id] = (int) $row->user_id;
        }

        return $authors;
    }
}
