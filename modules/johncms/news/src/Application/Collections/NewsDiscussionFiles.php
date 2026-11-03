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
            $allowed = $this->files->filterIdsInDirectory($ids, 'news_comments');
            sort($allowed);
            foreach ($allowed as $id) {
                if (! $this->discussion->referencesFile($id) && ! $this->collectionFiles->isReferenced($id)) {
                    $this->files->delete($id);
                }
            }
        });
    }
}
