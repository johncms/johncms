<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Comments\Domain\Comment;
use Johncms\Comments\Domain\CommentRepositoryInterface;

/**
 * The comments repository as a second request racing the first sees it: the comment found is
 * deleted by "somebody else" the moment it has been read.
 */
final readonly class VanishingCommentRepository implements CommentRepositoryInterface
{
    public function __construct(private CommentRepositoryInterface $inner)
    {
    }

    public function find(string $subjectType, int $subjectId, int $id): ?Comment
    {
        $comment = $this->inner->find($subjectType, $subjectId, $id);
        if ($comment !== null) {
            $this->inner->delete([$comment->id]);
        }

        return $comment;
    }

    public function unlinkAnswers(array $ids): void
    {
        $this->inner->unlinkAnswers($ids);
    }

    public function count(string $subjectType, int $subjectId): int
    {
        return $this->inner->count($subjectType, $subjectId);
    }

    public function countBySubjects(string $subjectType, array $subjectIds): array
    {
        return $this->inner->countBySubjects($subjectType, $subjectIds);
    }

    public function getPage(string $subjectType, int $subjectId, int $limit, int $offset): array
    {
        return $this->inner->getPage($subjectType, $subjectId, $limit, $offset);
    }

    public function countBefore(Comment $comment): int
    {
        return $this->inner->countBefore($comment);
    }

    public function findLatestOfUser(string $subjectType, int $userId): ?Comment
    {
        return $this->inner->findLatestOfUser($subjectType, $userId);
    }

    public function getLatest(string $subjectType, array $excludedSubjectIds, int $limit, int $offset): array
    {
        return $this->inner->getLatest($subjectType, $excludedSubjectIds, $limit, $offset);
    }

    public function countOfType(string $subjectType, array $excludedSubjectIds = []): int
    {
        return $this->inner->countOfType($subjectType, $excludedSubjectIds);
    }

    public function countByAuthors(string $subjectType, int $subjectId, array $userIds): array
    {
        return $this->inner->countByAuthors($subjectType, $subjectId, $userIds);
    }

    public function create(array $attributes): Comment
    {
        return $this->inner->create($attributes);
    }

    public function update(Comment $comment, array $attributes): void
    {
        $this->inner->update($comment, $attributes);
    }

    public function getLocked(string $subjectType, int $subjectId, array $ids): array
    {
        return $this->inner->getLocked($subjectType, $subjectId, $ids);
    }

    public function getOfAuthor(string $subjectType, int $subjectId, int $userId): array
    {
        return $this->inner->getOfAuthor($subjectType, $subjectId, $userId);
    }

    public function getOfSubjects(string $subjectType, array $subjectIds): array
    {
        return $this->inner->getOfSubjects($subjectType, $subjectIds);
    }

    public function getOfUser(int $userId): array
    {
        return $this->inner->getOfUser($userId);
    }

    public function countOfUser(int $userId): int
    {
        return $this->inner->countOfUser($userId);
    }

    public function delete(array $ids): void
    {
        $this->inner->delete($ids);
    }

    public function changeUserCommentCount(int $userId, int $delta): void
    {
        $this->inner->changeUserCommentCount($userId, $delta);
    }

    public function touchLastPost(int $userId, int $time): void
    {
        $this->inner->touchLastPost($userId, $time);
    }
}
