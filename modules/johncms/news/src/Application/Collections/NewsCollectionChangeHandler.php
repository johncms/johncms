<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;

final readonly class NewsCollectionChangeHandler implements CollectionChangeHandlerInterface
{
    public function __construct(
        private CollectionLookupApiInterface $lookup,
        private NewsDiscussionRepositoryInterface $discussion,
        private CollectionItemRelationsApiInterface $relations,
        private NewsDiscussionFiles $files,
        private ?NewsCollectionAvailability $availability = null,
    ) {
    }

    public function withinTransaction(CollectionChangeDTO $change): void
    {
        $this->availability?->assertAvailable($change->collectionId);
        if ($change->entity !== 'item' || $change->action !== 'deleted') {
            return;
        }
        $collection = $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY);
        if ($collection?->id !== $change->collectionId) {
            return;
        }
        $files = $this->discussion->deleteForItem($change->id);
        $this->relations->afterCommit(fn () => $this->files->cleanup($files, $change->collectionId));
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
    }
}
