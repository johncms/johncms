<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\NewFilesResultDTO;
use Johncms\Modules\Downloads\Application\Exceptions\DownloadNotFoundException;
use Johncms\Modules\Downloads\Domain\Repository\DownloadCategoryRepositoryInterface;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class ViewNewFilesUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
        private DownloadCategoryRepositoryInterface $categoryRepository,
    ) {
    }

    public function execute(int $page, int $perPage, int $categoryId = 0): NewFilesResultDTO
    {
        $directoryPrefix = null;

        if ($categoryId > 0) {
            $category = $this->categoryRepository->findById($categoryId);
            if ($category === null || ! is_dir($category->dir)) {
                throw new DownloadNotFoundException();
            }
            $directoryPrefix = $category->dir;
        }

        $files = $this->fileRepository->paginateNewFiles($page, $perPage, $directoryPrefix);

        return new NewFilesResultDTO($files, $categoryId);
    }
}
