<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\Api\CollectionFileReferencesApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsLegacyStorageRepositoryInterface;

/**
 * A file the import of the news of 9.x let go: of an article left out, or of a comment of no
 * article. It stays where something still shows it — a comment waiting for the import, a comment
 * of the core, an article, which then takes it over — and goes otherwise. Only the files of the
 * news and of their comments are touched.
 */
final readonly class NewsLegacyFileCleanup
{
    public function __construct(
        private FileStore $files,
        private CollectionFileReferencesApiInterface $collectionFiles,
        private CollectionItemRelationsApiInterface $relations,
        private NewsLegacyRepositoryInterface $legacy,
        private NewsLegacyStorageRepositoryInterface $storage,
    ) {
    }

    public function cleanup(int $id, int $collectionId): void
    {
        $this->relations->withCollectionLock($collectionId, function () use ($id): void {
            if ($this->files->filterIdsInDirectory([$id], 'news_comments') === [] && $this->files->filterIdsInDirectory([$id], 'news') === []) {
                return;
            }
            if ($this->legacy->commentsReferenceFile($id) || $this->storage->isHeldByComment($id)) {
                return;
            }
            if ($this->collectionFiles->isReferenced($id)) {
                $this->collectionFiles->transferOwnership($id, true);

                return;
            }
            $this->files->delete($id);
        });
    }
}
