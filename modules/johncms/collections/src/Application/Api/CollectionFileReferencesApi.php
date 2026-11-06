<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;

final readonly class CollectionFileReferencesApi implements CollectionFileReferencesApiInterface
{
    public function __construct(private CollectionFileRepositoryInterface $files, private CollectionItemFiles $attachments)
    {
    }

    public function transferOwnership(int $fileId, bool $toCollections): void
    {
        $this->files->register($fileId, false);
        $this->files->lockAsset($fileId);
        $this->files->setManaged($fileId, $toCollections);
    }

    public function registerExisting(int $collectionId, int $itemId, array $fileIds, bool $transferOwnership = false): void
    {
        $this->attachments->registerExisting($collectionId, $itemId, $fileIds, $transferOwnership);
    }

    public function registeredFileIds(int $collectionId, int $itemId): array
    {
        return $this->attachments->registeredFileIds($collectionId, $itemId);
    }

    public function isReferenced(int $fileId): bool
    {
        $this->files->lockAsset($fileId);
        return $this->files->hasLinks(fileId: $fileId, current: true) || $this->attachments->keepMentioned($fileId);
    }
}
