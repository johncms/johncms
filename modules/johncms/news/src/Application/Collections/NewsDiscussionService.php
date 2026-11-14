<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\News\Application\Exceptions\NewsCollectionUnavailableException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionValidationException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Modules\News\Application\UseCases\EnsureNewsDiscussionAccessUseCase;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;

final readonly class NewsDiscussionService implements NewsDiscussionServiceInterface
{
    public function __construct(
        private EnsureNewsDiscussionAccessUseCase $guard,
        private CollectionLookupApiInterface $lookup,
        private CollectionItemRelationsApiInterface $relations,
        private NewsDiscussionRepositoryInterface $discussion,
        private CurrentUser $user,
    ) {
    }

    public function vote(int $itemId, int $vote): NewsDiscussionStats
    {
        $this->guard->vote();
        if (! in_array($vote, [-1, 1], true)) {
            throw new NewsDiscussionValidationException(d__('news', 'Choose an upvote or a downvote.'));
        }
        $collectionId = $this->collection()->id;
        try {
            return $this->relations->withItemLock(
                collectionId: $collectionId,
                itemId: $itemId,
                operation: function () use ($itemId, $vote): NewsDiscussionStats {
                    $this->guard->vote();
                    $this->discussion->vote($itemId, $this->user->id(), $vote);

                    return $this->discussion->stats(ids: [$itemId], userId: $this->user->id(), current: true)[$itemId];
                },
                publishedOnly: true,
            );
        } catch (CollectionItemFormException $exception) {
            throw new NewsItemNotFoundException(message: d__('news', 'The requested article or section is not available.'), previous: $exception);
        }
    }

    private function collection(): CollectionRecordDTO
    {
        return $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
            ?? throw new NewsCollectionUnavailableException(d__('news', 'The news collection is missing. Run system:update.'));
    }
}
