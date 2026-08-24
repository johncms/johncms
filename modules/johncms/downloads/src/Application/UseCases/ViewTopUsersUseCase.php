<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\TopUsersResultDTO;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class ViewTopUsersUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function count(): int
    {
        return $this->fileRepository->countTopUsers();
    }

    public function getPage(int $limit, int $offset): TopUsersResultDTO
    {
        return new TopUsersResultDTO($this->fileRepository->getTopUsers($limit, $offset));
    }
}
