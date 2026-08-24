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

    public function count(int $categoryId = 0): int
    {
        return $this->fileRepository->countNewFiles($this->resolveDirectoryPrefix($categoryId));
    }

    public function getPage(int $limit, int $offset, int $categoryId = 0): NewFilesResultDTO
    {
        $files = $this->fileRepository->getNewFiles($limit, $offset, $this->resolveDirectoryPrefix($categoryId));

        return new NewFilesResultDTO($files, $categoryId);
    }

    private function resolveDirectoryPrefix(int $categoryId): ?string
    {
        if ($categoryId <= 0) {
            return null;
        }

        $category = $this->categoryRepository->findById($categoryId);
        if ($category === null || ! is_dir($category->dir)) {
            throw new DownloadNotFoundException();
        }

        return $category->dir;
    }
}
