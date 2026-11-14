<?php

declare(strict_types=1);

namespace Johncms\Comments;

/**
 * The kinds of object the modules let visitors discuss, collected from what they tagged.
 */
final readonly class CommentSubjects
{
    /** @var array<string, CommentSubjectInterface> */
    private array $subjects;

    /**
     * @param iterable<CommentSubjectInterface> $subjects
     */
    public function __construct(iterable $subjects)
    {
        $byType = [];
        foreach ($subjects as $subject) {
            $byType[$subject->type()] = $subject;
        }
        $this->subjects = $byType;
    }

    /** Null when no module that is switched on declares the kind. */
    public function get(string $type): ?CommentSubjectInterface
    {
        return $this->subjects[$type] ?? null;
    }
}
