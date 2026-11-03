<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionFileReferencesApiInterface;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;

final readonly class NewsDiscussionFiles
{
    public function __construct(private FileStore $files, private NewsDiscussionRepositoryInterface $discussion, private CollectionFileReferencesApiInterface $collectionFiles, private CollectionItemRelationsApiInterface $relations)
    {
    }

    /** @param list<int> $ids */
    public function cleanup(array $ids, int $collectionId): void
    {
        $this->relations->withCollectionLock($collectionId, function () use ($ids): void {
            $allowed = array_values(array_unique([...$this->files->filterIdsInDirectory($ids, 'news_comments'), ...$this->files->filterIdsInDirectory($ids, 'news')]));
            sort($allowed);
            foreach ($allowed as $id) {
                if ($this->discussion->referencesFile($id)) {
                    continue;
                }
                if ($this->collectionFiles->isReferenced($id)) {
                    $this->collectionFiles->transferOwnership($id, true);
                    continue;
                }
                $this->files->delete($id);
            }
        });
    }
}
