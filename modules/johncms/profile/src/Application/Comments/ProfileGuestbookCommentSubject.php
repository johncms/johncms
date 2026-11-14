<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Comments;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentWordingDTO;
use Johncms\Comments\CommentSubjectInterface;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetGuestbookContextUseCase;
use Johncms\Modules\Profile\Domain\Repository\GuestbookReadRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;

/**
 * The guestbook of a profile, kept as the comments of the core under the account itself.
 *
 * The owner removes the entries of their guestbook and keeps out those on their ignore list. What
 * others wrote after the owner last opened it is unread, and opening it reads everything.
 */
final readonly class ProfileGuestbookCommentSubject implements CommentSubjectInterface
{
    public const string TYPE = 'profile_guestbook';

    public function __construct(
        private GetGuestbookContextUseCase $context,
        private ProfileUserRepositoryInterface $users,
        private GuestbookReadRepositoryInterface $reads,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $access,
    ) {
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function find(int $id): ?CommentSubjectDTO
    {
        try {
            $owner = $this->context->execute($id);
        } catch (ProfileNotFoundException) {
            return null;
        }

        $url = '/profile/' . $owner->id;

        return new CommentSubjectDTO(
            type: self::TYPE,
            id: $owner->id,
            title: $owner->name,
            url: $url,
            discussionUrl: $url . '/guestbook',
            ownerId: $owner->id,
            ownerCanDelete: true,
            breadcrumbs: [
                ['name' => d__('profile', 'Profile') . ': ' . $owner->name, 'url' => $url],
            ],
            wording: $this->wording(),
        );
    }

    /** A guestbook keeps entries, not comments. */
    private function wording(): CommentWordingDTO
    {
        return new CommentWordingDTO(
            heading: d__('profile', 'Guestbook'),
            titled: d__('profile', 'Guestbook: %s'),
            count: static fn(int $count): string => sprintf(dn__('profile', '%d entry', '%d entries', $count), $count),
            empty: d__('profile', 'No entries yet. Be the first.'),
            write: d__('profile', 'Write an entry'),
            loginNotice: d__('profile', 'You are not logged in. You must be <a href="/login/">logged in</a> to write in the guestbook.'),
            added: d__('profile', 'The entry was added'),
            saved: d__('profile', 'The entry was saved'),
            deleted: d__('profile', 'The entry was deleted'),
            confirmDelete: d__('profile', 'Delete this entry?'),
            confirmDeleteAuthor: static fn(int $count, string $name): string => sprintf(
                dn__('profile', 'Delete %d entry of %s here?', 'Delete all %d entries of %s here?', $count),
                $count,
                $name
            ),
            deletedOfAuthor: static fn(int $count): string => sprintf(dn__('profile', '%d entry was deleted', '%d entries were deleted', $count), $count),
            editTitle: d__('profile', 'Edit entry'),
            emptyText: d__('profile', 'Enter the text of the entry'),
        );
    }

    public function hiddenIds(): array
    {
        return $this->access->allows(CorePermissions::USERS_UNCONFIRMED_VIEW) ? [] : $this->users->getUnconfirmedIds();
    }

    public function commentsChanged(int $id, int $delta): void
    {
        $this->users->changeGuestbookCount($id, $delta);
    }

    public function discussionViewed(CommentSubjectDTO $subject): void
    {
        if ($subject->ownerId === $this->currentUser->id()) {
            $this->reads->markRead($subject->id);
        }
    }
}
