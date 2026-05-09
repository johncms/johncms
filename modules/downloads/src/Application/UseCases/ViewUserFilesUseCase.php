<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\UserFilesResultDTO;
use Johncms\Modules\Downloads\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Users\User as UserModel;

final readonly class ViewUserFilesUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(int $userId, int $page, int $perPage): UserFilesResultDTO
    {
        $user = UserModel::query()->find($userId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $files = $this->fileRepository->paginateUserFiles($userId, $page, $perPage);

        return new UserFilesResultDTO($user, $files);
    }
}
