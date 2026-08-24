<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Services;

use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final class DownloadFilePathService
{
    public function __construct(
        private DownloadCategoryPathService $categoryPathService,
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function getFileSlug(DownloadFile $file): string
    {
        $slug = trim((string) $file->slug);

        return $slug !== '' ? $slug : 'file-' . $file->id;
    }

    /**
     * @return array{categoryPath: string, fileSlug: string, fileId: int}|null
     */
    public function parseFilePath(string $path): ?array
    {
        $normalized = trim($path, '/');
        if ($normalized === '' || ! str_starts_with($normalized, 'downloads/')) {
            return null;
        }

        $downloadsPath = trim(substr($normalized, strlen('downloads/')), '/');
        $lastSlashPos = strrpos($downloadsPath, '/');

        if ($lastSlashPos === false) {
            $categoryPath = '';
            $filePart = $downloadsPath;
        } else {
            $categoryPath = substr($downloadsPath, 0, $lastSlashPos);
            $filePart = substr($downloadsPath, $lastSlashPos + 1);
        }

        if ($filePart === '' || preg_match('/^(?<slug>[a-z0-9-]+)-(?<id>\d+)$/', $filePart, $matches) !== 1) {
            return null;
        }

        return [
            'categoryPath' => $categoryPath,
            'fileSlug'     => $matches['slug'],
            'fileId'       => (int) $matches['id'],
        ];
    }

    public function getFileUrl(DownloadFile $file): string
    {
        $slug = $this->getFileSlug($file);

        if ((int) $file->refid === 0) {
            return '/downloads/' . $slug . '-' . $file->id . '/';
        }

        $file->loadMissing('category');
        if ($file->category === null) {
            return '/downloads/' . $slug . '-' . $file->id . '/';
        }

        $categoryPath = $this->categoryPathService->getCategoryPath($file->category);

        return '/downloads/' . $categoryPath . '/' . $slug . '-' . $file->id . '/';
    }

    public function getFileUrlById(int $id): ?string
    {
        $file = $this->fileRepository->findFileWithCategory($id);
        if ($file === null) {
            return null;
        }

        return $this->getFileUrl($file);
    }
}
