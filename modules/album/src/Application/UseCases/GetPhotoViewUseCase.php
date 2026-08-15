<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Album\Application\DTO\PhotoPageResultDTO;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Album\Application\Services\PhotoPresenter;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Users\User;

final readonly class GetPhotoViewUseCase
{
    private const VOTE_MIN_POSTS = 5;
    private const VOTE_MIN_AGE = 259200; // 3 days since registration

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private AlbumPhotoRepositoryInterface $photoRepository,
        private AlbumVoteRepositoryInterface $voteRepository,
        private EnsureAlbumAccessUseCase $ensureAccess,
        private PhotoPresenter $photoPresenter,
        private User $currentUser,
    ) {
    }

    public function execute(int $photoId, ?int $page, ?string $submittedPassword, bool $addToProfile): PhotoPageResultDTO
    {
        $photo = $this->photoRepository->findById($photoId);
        if ($photo === null || $photo->album === null) {
            throw new AlbumPhotoNotFoundException();
        }

        $album = $photo->album;
        $this->ensureAccess->execute($album, $submittedPassword);

        $total = $this->photoRepository->countByAlbum($album->id);

        // The single-photo view is a one-per-page navigator across the album: the
        // requested image only sets the initial offset; ?page browses by offset.
        $offset = $page !== null
            ? $page - 1
            : $this->photoRepository->countPhotosAfter($album->id, $photoId);

        $displayed = $this->photoRepository->getPhotoByAlbumOffset($album->id, $offset);

        $successMessage = '';
        $detail = null;
        if ($displayed !== null) {
            if ($addToProfile && $album->user_id === $this->currentUser->id) {
                $this->copyToProfile($displayed);
                $successMessage = __('Photo added to the profile');
            }

            $isOwner = $displayed->user_id === $this->currentUser->id;
            $detail = $this->photoPresenter->presentDetail(
                $displayed,
                $this->canVote($displayed),
                $this->commentsEnabled(),
                $this->accessChecker->allows(AlbumPermissions::MODERATE) || $isOwner,
                $isOwner,
            );

            // Record a unique view after the DTO is built so the displayed
            // counter keeps the pre-increment value, like the legacy page.
            if (! $this->photoRepository->hasUserView($this->currentUser->id, $displayed->id)) {
                $this->photoRepository->addView($this->currentUser->id, $displayed->id, time());
                $this->photoRepository->refreshViewsCount($displayed->id);
            }
        }

        return new PhotoPageResultDTO(
            albumId: $album->id,
            ownerId: $album->user_id,
            photo: $detail,
            total: $total,
            offset: max(0, $offset),
            successMessage: $successMessage,
        );
    }

    private function canVote(AlbumPhoto $photo): bool
    {
        if (! $this->isViewerEligibleToVote() || $photo->user_id === $this->currentUser->id) {
            return false;
        }

        return $this->voteRepository->filterVotedPhotoIds($this->currentUser->id, [$photo->id]) === [];
    }

    private function isViewerEligibleToVote(): bool
    {
        return empty($this->currentUser->ban)
            && $this->currentUser->postforum > self::VOTE_MIN_POSTS
            && $this->currentUser->datereg < (time() - self::VOTE_MIN_AGE);
    }

    /**
     * The comments of the photos are switched by the setting of the downloads, which is where
     * this module took them from. The setting stays where it is; who may read them past it is
     * the permission.
     */
    private function commentsEnabled(): bool
    {
        $config = config('johncms');

        return ! empty($config['mod_down_comm'])
            || $this->accessChecker->allows(AlbumPermissions::COMMENTS_ALWAYS_VIEW);
    }

    private function copyToProfile(AlbumPhoto $photo): void
    {
        $albumDir = UPLOAD_PATH . 'users/album/' . $photo->user_id . '/';
        $profileDir = UPLOAD_PATH . 'users/photo/';

        if (is_file($albumDir . $photo->tmb_name)) {
            copy($albumDir . $photo->tmb_name, $profileDir . $this->currentUser->id . '_small.jpg');
        }
        if (is_file($albumDir . $photo->img_name)) {
            copy($albumDir . $photo->img_name, $profileDir . $this->currentUser->id . '.jpg');
        }
    }
}
