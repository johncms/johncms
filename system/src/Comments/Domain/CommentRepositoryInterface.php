<?php

declare(strict_types=1);

namespace Johncms\Comments\Domain;

interface CommentRepositoryInterface
{
    public function count(string $subjectType, int $subjectId): int;

    /**
     * Oldest first.
     *
     * @return list<Comment>
     */
    public function getPage(string $subjectType, int $subjectId, int $limit, int $offset): array;

    /** Comments of the same subject that come before this one. */
    public function countBefore(Comment $comment): int;

    /** The comment only when it belongs to this very subject. */
    public function find(string $subjectType, int $subjectId, int $id): ?Comment;

    public function findLatestOfUser(string $subjectType, int $userId): ?Comment;

    /**
     * The comments of every object of the kind but the excluded ones, newest first.
     *
     * @param list<int> $excludedSubjectIds
     * @return list<Comment>
     */
    public function getLatest(string $subjectType, array $excludedSubjectIds, int $limit, int $offset): array;

    /**
     * @param list<int> $excludedSubjectIds
     */
    public function countOfType(string $subjectType, array $excludedSubjectIds = []): int;

    /**
     * How many comments each of the authors left under the subject.
     *
     * @param list<int> $userIds
     * @return array<int, int>
     */
    public function countByAuthors(string $subjectType, int $subjectId, array $userIds): array;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Comment;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(Comment $comment, array $attributes): void;

    /**
     * Those of the comments that are still there, locked until the transaction ends: id => author.
     *
     * @param list<int> $ids
     * @return array<int, int>
     */
    public function getLocked(string $subjectType, int $subjectId, array $ids): array;

    /**
     * The comments of the author under the subject: id => author.
     *
     * @return array<int, int>
     */
    public function getOfAuthor(string $subjectType, int $subjectId, int $userId): array;

    /**
     * The comments of the subjects: id => author.
     *
     * @param list<int> $subjectIds
     * @return array<int, int>
     */
    public function getOfSubjects(string $subjectType, array $subjectIds): array;

    /**
     * The comments the user wrote, wherever they are.
     *
     * @return list<array{id: int, subject_type: string, subject_id: int}>
     */
    public function getOfUser(int $userId): array;

    public function countOfUser(int $userId): int;

    /**
     * @param list<int> $ids
     */
    public function delete(array $ids): void;

    /** Change the number of comments the user has written; it never goes below zero. */
    public function changeUserCommentCount(int $userId, int $delta): void;

    /** What the antiflood measures the next message from. */
    public function touchLastPost(int $userId, int $time): void;
}
