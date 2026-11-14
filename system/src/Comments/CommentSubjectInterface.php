<?php

declare(strict_types=1);

namespace Johncms\Comments;

/**
 * An object of a module that visitors discuss through the comments of the core.
 *
 * The module implements it once per kind of object; the container tags it. The core never knows
 * the tables of the module: it asks here whether an object exists, what to call it, and what the
 * module counts.
 */
interface CommentSubjectInterface
{
    /** Key of the kind of object, stored with every comment. At most 32 characters, never renamed. */
    public function type(): string;

    /** The object, or null when it does not exist or the current visitor may not see it. */
    public function find(int $id): ?CommentSubjectDTO;

    /**
     * The number of comments under the object changed by the delta. Called inside the
     * transaction that changed them; an atomic `UPDATE … SET count = count + ?`, never a total
     * counted beforehand.
     */
    public function commentsChanged(int $id, int $delta): void;
}
