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

    public function execute(int $page, int $perPage): TopUsersResultDTO
    {
        $users = $this->fileRepository->paginateTopUsers($page, $perPage);

        return new TopUsersResultDTO($users);
    }
}
