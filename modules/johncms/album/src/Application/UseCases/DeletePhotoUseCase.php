<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Comments\Application\CommentsService;
use Johncms\Modules\Album\Application\Comments\AlbumCommentSubject;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;
use Johncms\Modules\Album\Infrastructure\Storage\AlbumPhotoStorage;

final readonly class DeletePhotoUseCase
{
    public function __construct(
        private AlbumPhotoRepositoryInterface $photoRepository,
        private AlbumVoteRepositoryInterface $voteRepository,
        private CommentsService $comments,
        private AlbumPhotoStorage $photos,
    ) {
    }

    public function execute(AlbumPhoto $photo): void
    {
        // Remove the original image and its thumbnail from disk.
        $this->photos->delete($photo->user_id, (string) $photo->img_name);
        $this->photos->delete($photo->user_id, (string) $photo->tmb_name);

        $this->voteRepository->deleteByPhotoIds([$photo->id]);
        $this->comments->deleteForSubjects(AlbumCommentSubject::TYPE, [$photo->id]);
        $this->photoRepository->deleteById($photo->id);
    }
}
