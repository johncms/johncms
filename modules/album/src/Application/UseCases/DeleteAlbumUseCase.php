<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumCommentRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;

final readonly class DeleteAlbumUseCase
{
    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $photoRepository,
        private AlbumVoteRepositoryInterface $voteRepository,
        private AlbumCommentRepositoryInterface $commentRepository,
    ) {
    }

    public function execute(Album $album): void
    {
        $photos = $this->photoRepository->getByAlbum($album->id);

        $photoIds = [];
        $albumDir = UPLOAD_PATH . 'users/album/' . $album->user_id . '/';
        foreach ($photos as $photo) {
            $photoIds[] = $photo->id;
            // Remove the original image and its thumbnail from disk.
            @unlink($albumDir . $photo->img_name);
            @unlink($albumDir . $photo->tmb_name);
        }

        $this->voteRepository->deleteByPhotoIds($photoIds);
        $this->commentRepository->deleteByPhotoIds($photoIds);
        $this->photoRepository->deleteByAlbum($album->id);
        $this->albumRepository->delete($album);
    }
}
