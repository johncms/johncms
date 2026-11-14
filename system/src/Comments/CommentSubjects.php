<?php

declare(strict_types=1);

namespace Johncms\Comments;

/**
 * The kinds of object the modules let visitors discuss, collected from what they tagged.
 *
 * The subjects are built on the first question, not with this service: a subject may itself
 * depend, through its module, on a service that deletes comments.
 */
final class CommentSubjects
{
    /** @var array<string, CommentSubjectInterface>|null */
    private ?array $subjects = null;

    /**
     * @param iterable<CommentSubjectInterface> $tagged
     */
    public function __construct(private readonly iterable $tagged)
    {
    }

    /** Null when no module that is switched on declares the kind. */
    public function get(string $type): ?CommentSubjectInterface
    {
        if ($this->subjects === null) {
            $this->subjects = [];
            foreach ($this->tagged as $subject) {
                $this->subjects[$subject->type()] = $subject;
            }
        }

        return $this->subjects[$type] ?? null;
    }
}
