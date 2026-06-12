<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\FavoritesResultDTO;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class ViewFavoritesUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function count(int $userId): int
    {
        return $this->fileRepository->countFavorites($userId);
    }

    public function getPage(int $userId, int $limit, int $offset): FavoritesResultDTO
    {
        return new FavoritesResultDTO($this->fileRepository->getFavorites($userId, $limit, $offset));
    }
}
