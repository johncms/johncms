<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Comments\Application\CommentsService;
use Johncms\Modules\News\Application\Comments\NewsCommentSubject;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;

final readonly class NewsCollectionChangeHandler implements CollectionChangeHandlerInterface
{
    public function __construct(
        private CollectionLookupApiInterface $lookup,
        private NewsDiscussionRepositoryInterface $discussion,
        private CommentsService $comments,
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
        $this->comments->deleteForSubjects(NewsCommentSubject::TYPE, [$change->id]);
        $this->discussion->deleteVotesForItem($change->id);
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
    }
}
