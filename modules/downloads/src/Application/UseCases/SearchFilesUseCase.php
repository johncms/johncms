<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\SearchFilesResultDTO;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class SearchFilesUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(string $rawQuery, bool $searchInDescription, int $page, int $perPage): SearchFilesResultDTO
    {
        $sanitizedQuery = (string) preg_replace("/[^\w\x7F-\xFF\s]/", ' ', $rawQuery);
        $files = $this->fileRepository->searchFiles($sanitizedQuery, $searchInDescription, $page, $perPage);

        return new SearchFilesResultDTO($files, $sanitizedQuery, $searchInDescription);
    }
}
