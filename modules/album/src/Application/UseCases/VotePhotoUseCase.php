<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Album\Domain\Enums\VoteType;
use Johncms\Modules\Album\Domain\Models\AlbumPhoto;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumVoteRepositoryInterface;

final readonly class VotePhotoUseCase
{
    public function __construct(
        private AlbumVoteRepositoryInterface $voteRepository,
        private AlbumPhotoRepositoryInterface $photoRepository,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(AlbumPhoto $photo, VoteType $type): void
    {
        $this->voteRepository->addVote($this->currentUser->id(), $photo->id, $type->storedValue());

        if ($type === VoteType::Plus) {
            $this->photoRepository->incrementVotePlus($photo->id);
        } else {
            $this->photoRepository->incrementVoteMinus($photo->id);
        }
    }
}
