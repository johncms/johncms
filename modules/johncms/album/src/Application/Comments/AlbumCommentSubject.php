<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Comments;

use Johncms\Auth\CurrentUser;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjectInterface;
use Johncms\Modules\Album\Application\Exceptions\AlbumAccessDeniedException;
use Johncms\Modules\Album\Application\Exceptions\AlbumPasswordRequiredException;
use Johncms\Modules\Album\Application\UseCases\EnsureAlbumAccessUseCase;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;

/**
 * A photo of an album, discussed through the comments of the core.
 *
 * Whoever may see the album may read the discussion; the owner of the album removes the comments
 * under its photos and keeps out those on their ignore list. A comment of somebody else marks the
 * photo as having unread comments, and the owner opening the discussion marks them read.
 */
final readonly class AlbumCommentSubject implements CommentSubjectInterface
{
    public const string TYPE = 'album';

    public function __construct(
        private AlbumPhotoRepositoryInterface $photos,
        private EnsureAlbumAccessUseCase $ensureAccess,
        private CurrentUser $currentUser,
    ) {
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function find(int $id): ?CommentSubjectDTO
    {
        $photo = $this->photos->findById($id);
        if ($photo === null || $photo->album === null) {
            return null;
        }

        try {
            $this->ensureAccess->execute($photo->album);
        } catch (AlbumAccessDeniedException | AlbumPasswordRequiredException) {
            return null;
        }

        $ownerId = $photo->user_id;
        $url = '/album/photo/' . $photo->id;

        return new CommentSubjectDTO(
            type: self::TYPE,
            id: $photo->id,
            title: $photo->description !== '' ? strip_tags($photo->description) : d__('album', 'Photo'),
            url: $url,
            discussionUrl: $url . '/comments',
            ownerId: $ownerId,
            open: $photo->comments,
            ownerCanDelete: true,
            breadcrumbs: [
                ['name' => d__('album', 'Albums'), 'url' => '/album'],
                [
                    'name' => $ownerId === $this->currentUser->id() ? d__('album', 'Your albums') : d__('album', 'User albums'),
                    'url'  => '/album/user/' . $ownerId,
                ],
                ['name' => (string) $photo->album->name, 'url' => '/album/' . $photo->album_id],
                ['name' => d__('album', 'Photo'), 'url' => $url],
            ],
        );
    }

    public function commentsChanged(int $id, int $delta): void
    {
        $this->photos->changeCommentCount($id, $delta);

        if ($delta > 0 && $this->photos->findById($id)?->user_id !== $this->currentUser->id()) {
            $this->photos->setUnreadComments($id, true);
        }
    }

    public function discussionViewed(CommentSubjectDTO $subject): void
    {
        if ($subject->ownerId === $this->currentUser->id()) {
            $this->photos->setUnreadComments($subject->id, false);
        }
    }
}
