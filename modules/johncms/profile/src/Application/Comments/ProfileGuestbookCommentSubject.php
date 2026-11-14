<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Comments;

use Johncms\Auth\CurrentUser;
use Johncms\Comments\CommentSubjectDTO;
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
            heading: d__('profile', 'Guestbook'),
        );
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
