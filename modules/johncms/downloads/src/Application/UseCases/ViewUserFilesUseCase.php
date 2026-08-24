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

    public function count(int $userId): int
    {
        $this->ensureUserExists($userId);

        return $this->fileRepository->countUserFiles($userId);
    }

    public function getPage(int $userId, int $limit, int $offset): UserFilesResultDTO
    {
        $user = $this->ensureUserExists($userId);

        $files = $this->fileRepository->getUserFiles($userId, $limit, $offset);

        return new UserFilesResultDTO($user, $files);
    }

    private function ensureUserExists(int $userId): UserModel
    {
        $user = UserModel::query()->find($userId);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
