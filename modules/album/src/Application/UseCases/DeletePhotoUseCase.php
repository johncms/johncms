<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumCommentRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;

final readonly class DeletePhotoUseCase
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $photoRepository,
        private AlbumVoteRepositoryInterface $voteRepository,
        private AlbumCommentRepositoryInterface $commentRepository,
    ) {
    }

    public function execute(AlbumPhoto $photo): void
    {
        // Remove the original image and its thumbnail from disk.
        $albumDir = UPLOAD_PATH . 'users/album/' . $photo->user_id . '/';
        @unlink($albumDir . $photo->img_name);
        @unlink($albumDir . $photo->tmb_name);

        $this->voteRepository->deleteByPhotoIds([$photo->id]);
        $this->commentRepository->deleteByPhotoIds([$photo->id]);
        $this->photoRepository->deleteById($photo->id);
    }
}
