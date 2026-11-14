<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjectInterface;
use RuntimeException;

/** A subject of the test: every object exists, and the changes of its counter are recorded. */
final class TestCommentSubject implements CommentSubjectInterface
{
    /** @var list<array{int, int}> */
    public array $changes = [];

    public bool $failing = false;

    /** @var list<int> */
    public array $viewed = [];

    public function type(): string
    {
        return 'test_subject';
    }

    public function find(int $id): CommentSubjectDTO
    {
        return new CommentSubjectDTO(type: 'test_subject', id: $id, title: 'An object', url: '/test/' . $id, discussionUrl: '/test/' . $id . '/comments');
    }

    public function commentsChanged(int $id, int $delta): void
    {
        if ($this->failing) {
            throw new RuntimeException('The counter failed.');
        }
        $this->changes[] = [$id, $delta];
    }

    public function discussionViewed(CommentSubjectDTO $subject): void
    {
        $this->viewed[] = $subject->id;
    }
}
