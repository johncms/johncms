<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPublicApiInterface;
use Johncms\Modules\News\Application\Collections\NewsCollectionDeclaration;
use Johncms\Modules\News\Application\Exceptions\NewsCollectionUnavailableException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionAccessDeniedException;
use Johncms\Modules\News\Application\Services\NewsPermissions;

final readonly class EnsureNewsDiscussionAccessUseCase
{
    public function __construct(
        private CollectionLookupApiInterface $lookup,
        private CollectionsPublicApiInterface $items,
        private CurrentUser $user,
        private AccessCheckerInterface $access,
        private \Johncms\Modules\News\Application\Collections\NewsCollectionAvailability $availability,
    ) {
    }

    public function read(int $itemId): void
    {
        $collection = $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
            ?? throw new NewsCollectionUnavailableException(d__('news', 'The news collection is missing. Run system:update.'));
        if ($this->items->findItem($collection->id, $itemId) === null) {
            throw new NewsItemNotFoundException(d__('news', 'The requested article or section is not available.'));
        }
    }

    public function comment(): void
    {
        if (! $this->availability->ready()) {
            throw new NewsDiscussionAccessDeniedException(d__('news', 'News is unavailable until system:update completes.'));
        }
        if (! $this->user->isValid() || ! $this->access->allows(NewsPermissions::COMMENTS_POST)) {
            throw new NewsDiscussionAccessDeniedException(d__('news', 'You are not allowed to comment on the news.'));
        }
    }

    public function vote(): void
    {
        if (! $this->availability->ready()) {
            throw new NewsDiscussionAccessDeniedException(d__('news', 'News is unavailable until system:update completes.'));
        }
        if (! $this->user->isValid() || ! $this->access->allows(NewsPermissions::VOTE)) {
            throw new NewsDiscussionAccessDeniedException(d__('news', 'You are not allowed to vote on the news.'));
        }
    }

    public function delete(int $authorId): void
    {
        if (! $this->availability->ready()) {
            throw new NewsDiscussionAccessDeniedException(d__('news', 'News is unavailable until system:update completes.'));
        }
        if (! $this->user->isValid() || ($this->user->id() !== $authorId && ! $this->access->allows(NewsPermissions::COMMENTS_MODERATE))) {
            throw new NewsDiscussionAccessDeniedException(d__('news', 'You are not allowed to delete this comment.'));
        }
    }
}
