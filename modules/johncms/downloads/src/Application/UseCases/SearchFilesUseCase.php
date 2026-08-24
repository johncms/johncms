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

    public function count(string $rawQuery, bool $searchInDescription): int
    {
        return $this->fileRepository->countSearchFiles($this->sanitize($rawQuery), $searchInDescription);
    }

    public function getPage(string $rawQuery, bool $searchInDescription, int $limit, int $offset): SearchFilesResultDTO
    {
        $sanitizedQuery = $this->sanitize($rawQuery);
        $files = $this->fileRepository->getSearchFiles($sanitizedQuery, $searchInDescription, $limit, $offset);

        return new SearchFilesResultDTO($files, $sanitizedQuery, $searchInDescription);
    }

    private function sanitize(string $rawQuery): string
    {
        return (string) preg_replace("/[^\w\x7F-\xFF\s]/", ' ', $rawQuery);
    }
}
