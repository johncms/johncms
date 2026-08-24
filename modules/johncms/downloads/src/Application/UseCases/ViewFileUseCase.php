<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\ViewFileResultDTO;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\Users\User as UserModel;

final readonly class ViewFileUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(int $id): ViewFileResultDTO
    {
        $file = $this->fileRepository->findFile($id);

        if ($file === null) {
            throw new FileNotFoundException();
        }

        $additionalFiles = $this->fileRepository->findAdditionalFiles($id);
        $uploadUser = $file->user_id > 0
            ? UserModel::query()->select('id', 'name')->find($file->user_id)
            : null;

        return new ViewFileResultDTO($file, $additionalFiles, $uploadUser);
    }
}
