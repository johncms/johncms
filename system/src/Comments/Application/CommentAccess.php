<?php

declare(strict_types=1);

namespace Johncms\Comments\Application;

use DateTimeImmutable;
use DateTimeZone;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\Exceptions\CommentAccessDeniedException;
use Johncms\Comments\Application\Exceptions\CommentSubjectNotFoundException;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjects;
use Johncms\Comments\Domain\Comment;
use Johncms\Users\IgnoreListCheckerInterface;

/**
 * Who may do what in a discussion. Asked on every request, not only when a form is drawn.
 *
 * - Writing: a signed-in visitor, the discussion open, no ban of the kinds the subject names, not
 *   blocked by the owner of the object.
 * - Editing: the moderator always, the owner where the subject lets them, the author for five
 *   minutes after writing while they may still write here.
 * - Deleting: the moderator, and the owner where the subject lets them.
 *
 * A ban of the subject's kinds closes everything to its holder, the moderator included.
 */
final readonly class CommentAccess
{
    /** How long the author may edit their comment. The same window as a post of the forum. */
    public const int AUTHOR_EDIT_SECONDS = 300;

    public function __construct(
        private CommentSubjects $subjects,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $access,
        private IgnoreListCheckerInterface $ignoreList,
    ) {
    }

    /**
     * @throws CommentSubjectNotFoundException
     */
    public function subject(string $type, int $id): CommentSubjectDTO
    {
        return $this->subjects->get($type)?->find($id) ?? throw new CommentSubjectNotFoundException();
    }

    /** Why the visitor may not write under the subject; null when they may. */
    public function writeRefusal(CommentSubjectDTO $subject): ?string
    {
        if (! $this->currentUser->isValid()) {
            return d__('system', 'Sign in to write a comment.');
        }
        if (! $subject->open) {
            return d__('system', 'The comments are closed.');
        }
        if ($this->isBanned($subject)) {
            return d__('system', 'You are banned and cannot write comments here.');
        }
        if ($subject->ownerId !== null && $this->ignoreList->isBlockedBy($subject->ownerId, $this->currentUser->id())) {
            return d__('system', 'The owner has added you to the ignore list.');
        }

        return null;
    }

    /**
     * @throws CommentAccessDeniedException
     */
    public function ensureCanWrite(CommentSubjectDTO $subject): void
    {
        $refusal = $this->writeRefusal($subject);
        if ($refusal !== null) {
            throw new CommentAccessDeniedException($refusal);
        }
    }

    public function canEdit(CommentSubjectDTO $subject, Comment $comment): bool
    {
        if (! $this->currentUser->isValid() || $this->isBanned($subject)) {
            return false;
        }
        if ($this->isModerator() || ($subject->ownerCanEdit && $this->isOwner($subject))) {
            return true;
        }

        return $comment->user_id !== 0
            && $comment->user_id === $this->currentUser->id()
            && $this->writeRefusal($subject) === null
            && $this->writtenAt($comment) + self::AUTHOR_EDIT_SECONDS >= time();
    }

    /**
     * @throws CommentAccessDeniedException
     */
    public function ensureCanEdit(CommentSubjectDTO $subject, Comment $comment): void
    {
        if (! $this->canEdit($subject, $comment)) {
            throw new CommentAccessDeniedException(d__('system', 'Access denied'));
        }
    }

    public function canDelete(CommentSubjectDTO $subject): bool
    {
        if (! $this->currentUser->isValid() || $this->isBanned($subject)) {
            return false;
        }

        return $this->isModerator() || ($subject->ownerCanDelete && $this->isOwner($subject));
    }

    /**
     * @throws CommentAccessDeniedException
     */
    public function ensureCanDelete(CommentSubjectDTO $subject): void
    {
        if (! $this->canDelete($subject)) {
            throw new CommentAccessDeniedException(d__('system', 'Access denied'));
        }
    }

    /** The address and the browser of an author are for the staff only. */
    public function canSeeOrigin(): bool
    {
        return $this->access->allows(CorePermissions::USERS_ORIGIN_VIEW);
    }

    private function isModerator(): bool
    {
        return $this->access->allows(CorePermissions::COMMENTS_MODERATE);
    }

    private function isOwner(CommentSubjectDTO $subject): bool
    {
        return $subject->ownerId !== null && $subject->ownerId === $this->currentUser->id();
    }

    private function isBanned(CommentSubjectDTO $subject): bool
    {
        return array_intersect_key($this->currentUser->user()->ban, array_flip($subject->bans)) !== [];
    }

    private function writtenAt(Comment $comment): int
    {
        return (new DateTimeImmutable($comment->created_at, new DateTimeZone('UTC')))->getTimestamp();
    }
}
