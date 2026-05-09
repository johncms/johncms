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

    public function execute(int $userId, int $page, int $perPage): FavoritesResultDTO
    {
        $files = $this->fileRepository->paginateFavorites($userId, $page, $perPage);

        return new FavoritesResultDTO($files);
    }
}
