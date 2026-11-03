<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Content\ContentRendererInterface;
use Johncms\Content\ContentContext;
use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Query\FieldFilterDTO;
use Johncms\Modules\Collections\Domain\Enums\FilterOperator;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\News\Application\DTO\NewsAuthorDTO;
use Johncms\Modules\News\Application\DTO\NewsCommentViewDTO;
use Johncms\Modules\News\Application\DTO\NewsCommentPageDTO;
use Johncms\Modules\News\Application\Exceptions\NewsCollectionUnavailableException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionValidationException;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\Modules\News\Application\UseCases\EnsureNewsDiscussionAccessUseCase;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\Modules\News\Domain\ValueObjects\NewsDiscussionStats;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;
use Johncms\View\CommentFeed\FeedComment;

final readonly class NewsDiscussionService implements NewsDiscussionServiceInterface
{
    public function __construct(
        private EnsureNewsDiscussionAccessUseCase $guard,
        private CollectionLookupApiInterface $lookup,
        private CollectionItemRelationsApiInterface $relations,
        private CollectionsApiInterface $items,
        private CollectionTransactionInterface $transaction,
        private NewsDiscussionRepositoryInterface $discussion,
        private NewsDiscussionFiles $cleanup,
        private FileStore $files,
        private CurrentUser $user,
        private AccessCheckerInterface $access,
        private UserRepositoryInterface $users,
        private ContentRendererInterface $content,
        private StaffTitles $staff,
        private DateFormatterInterface $dates,
        private NewsCollectionOwnerUrls $urls,
    ) {
    }

    public function comments(int $itemId, int $limit = 20, int $offset = 0): NewsCommentPageDTO
    {
        $this->guard->read($itemId);
        if ($limit < 1 || $limit > 1000 || $offset < 0 || $offset > 1000000) {
            throw new \InvalidArgumentException('Use a valid comment limit and offset.');
        }
        $rows = $this->discussion->comments($itemId, $limit, $offset);
        $users = $this->users->findMany(array_values(array_unique(array_map(static fn ($row): int => $row->user_id, $rows))));
        $this->staff->preload(array_keys($users));
        $moderates = $this->access->allows(NewsPermissions::COMMENTS_MODERATE);
        $comments = [];
        foreach ($rows as $row) {
            $author = $users[$row->user_id] ?? null;
            $canReply = $this->user->isValid() && $this->user->id() !== $row->user_id && $this->access->allows(NewsPermissions::COMMENTS_POST);
            $comments[] = new NewsCommentViewDTO(
                $row->id,
                $itemId,
                $row->user_id,
                $this->content->render($row->text, new ContentContext(adminSmilies: $this->staff->isStaff($row->user_id))),
                $this->dates->format((new \DateTimeImmutable($row->getRawOriginal('created_at'), new \DateTimeZone('UTC')))->getTimestamp()),
                $author !== null ? new NewsAuthorDTO($author->id, $author->name) : null,
                $this->user->isValid() && ($moderates || $this->user->id() === $row->user_id),
                $canReply,
                $moderates ? ($row->user_data ?? []) : []
            );
        }
        return new NewsCommentPageDTO($comments, $this->discussion->countComments($itemId), $limit, $offset);
    }

    public function addComment(int $itemId, string $text, array $files = [], array $metadata = []): int
    {
        $this->guard->comment();
        $text = trim($text);
        if ($text === '' || $this->content->renderOrNull($text) === null) {
            throw new NewsDiscussionValidationException(d__('news', 'Enter the comment text'));
        }
        foreach ($files as $id) {
            if (! is_int($id) || $id < 1) {
                throw new NewsDiscussionValidationException(d__('news', 'One of the comment attachments is not available.'));
            }
        }
        $files = array_values(array_unique($files));
        $collection = $this->collection();
        return $this->withItem($collection->id, $itemId, function () use ($itemId, $text, $files, $metadata): int {
            $this->guard->comment();
            $allowed = $this->files->filterIdsInDirectory($files, 'news_comments');
            if (count($allowed) !== count($files)) {
                throw new NewsDiscussionValidationException(d__('news', 'One of the comment attachments is not available.'));
            }
            return $this->discussion->addComment($itemId, $this->user->id(), $text, $metadata, $files);
        });
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->discussion->findComment($commentId);
        if ($comment === null) {
            throw new NewsItemNotFoundException(d__('news', 'The comment is not available.'));
        }
        $this->guard->delete($comment->user_id);
        $collection = $this->collection();
        $this->withItem($collection->id, $comment->item_id, function () use ($comment, $collection): void {
            $current = $this->discussion->findComment($comment->id, current: true);
            if ($current === null) {
                return;
            }
            if ($current->item_id !== $comment->item_id) {
                throw new NewsItemNotFoundException(d__('news', 'The comment is not available.'));
            }
            $this->guard->delete($current->user_id);
            $files = $this->discussion->deleteComment($current->id);
            $this->transaction->afterCommit(fn () => $this->cleanup->cleanup($files, $collection->id));
        });
    }

    public function vote(int $itemId, int $vote): NewsDiscussionStats
    {
        $this->guard->vote();
        if (! in_array($vote, [-1, 1], true)) {
            throw new NewsDiscussionValidationException(d__('news', 'Choose an upvote or a downvote.'));
        }
        return $this->withItem($this->collection()->id, $itemId, function () use ($itemId, $vote): NewsDiscussionStats {
            $this->guard->vote();
            $this->discussion->vote($itemId, $this->user->id(), $vote);
            return $this->discussion->stats([$itemId], $this->user->id(), current: true)[$itemId];
        });
    }

    public function latestFeed(int $limit): array
    {
        if (! $this->access->allows(NewsPermissions::COMMENTS_MODERATE)) {
            return [];
        }
        if ($limit < 1 || $limit > 1000) {
            throw new \InvalidArgumentException('Use a comment feed limit between 1 and 1000.');
        }
        $collection = $this->collection();
        $rows = $this->discussion->latestComments($limit);
        if ($rows === []) {
            return [];
        }
        $ids = array_values(array_unique(array_map(static fn ($row): int => $row->item_id, $rows)));
        $items = $this->items->getItems(new ContentCollectionItemQuery(
            $collection->id,
            filters: [new FieldFilterDTO('item.id', FilterOperator::In, $ids)],
            onlyActive: false,
            limit: 1000,
            fields: [],
            includeText: false
        ));
        $byId = [];
        foreach ($items as $item) {
            $byId[$item->id] = $item;
        }
        $users = $this->users->findMany(array_values(array_unique(array_map(static fn ($row): int => $row->user_id, $rows))));
        $result = [];
        foreach ($rows as $row) {
            $item = $byId[$row->item_id] ?? null;
            if ($item === null) {
                continue;
            }
            $url = $this->urls->itemUrl($collection, $item);
            $result[] = new FeedComment(
                d__('news', 'News'),
                $item->name,
                ($url ?? '/news/') . '#article-comments',
                $users[$row->user_id]->id ?? 0,
                $users[$row->user_id]->name ?? '',
                (string) $this->content->render($row->text),
                (new \DateTimeImmutable($row->getRawOriginal('created_at'), new \DateTimeZone('UTC')))->getTimestamp()
            );
        }
        return $result;
    }

    public function countFeed(): int
    {
        return $this->access->allows(NewsPermissions::COMMENTS_MODERATE) ? $this->discussion->countAllComments() : 0;
    }

    private function collection(): CollectionRecordDTO
    {
        return $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
            ?? throw new NewsCollectionUnavailableException(d__('news', 'The news collection is missing. Run system:update.'));
    }

    /** @template T
     * @param callable(): T $operation
     * @return T
     */
    private function withItem(int $collectionId, int $itemId, callable $operation): mixed
    {
        try {
            return $this->relations->withItemLock($collectionId, $itemId, $operation, publishedOnly: true);
        } catch (CollectionItemFormException $exception) {
            throw new NewsItemNotFoundException(d__('news', 'The requested article or section is not available.'), previous: $exception);
        }
    }
}
