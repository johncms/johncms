<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Domain\Models\Album;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;

final readonly class MoveAlbumUseCase
{
    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
    ) {
    }

    public function moveUp(Album $album): void
    {
        $neighbour = $this->albumRepository->findPreviousBySort($album->user_id, $album->sort);
        if ($neighbour !== null) {
            $this->swapSort($album, $neighbour);
        }
    }

    public function moveDown(Album $album): void
    {
        $neighbour = $this->albumRepository->findNextBySort($album->user_id, $album->sort);
        if ($neighbour !== null) {
            $this->swapSort($album, $neighbour);
        }
    }

    private function swapSort(Album $album, Album $neighbour): void
    {
        $albumSort = $album->sort;
        $this->albumRepository->setSort($album, $neighbour->sort);
        $this->albumRepository->setSort($neighbour, $albumSort);
    }
}
