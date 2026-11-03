<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;

final readonly class CollectionFileReferencesApi implements CollectionFileReferencesApiInterface
{
    public function __construct(private CollectionFileRepositoryInterface $files)
    {
    }

    public function transferOwnership(int $fileId, bool $toCollections): void
    {
        $this->files->register($fileId, false);
        $this->files->lockAsset($fileId);
        $this->files->setManaged($fileId, $toCollections);
    }

    public function isReferenced(int $fileId): bool
    {
        $this->files->lockAsset($fileId);
        return $this->files->hasLinks($fileId, current: true);
    }
}
