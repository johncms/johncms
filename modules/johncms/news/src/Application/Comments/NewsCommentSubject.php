<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Comments;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjectInterface;
use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\Exceptions\NewsCollectionUnavailableException;
use Johncms\Modules\News\Application\Services\NewsPermissions;

/**
 * The discussion of a news article, shown within the page of the article.
 *
 * The article counts nothing itself: the number of its comments is asked from the core where a
 * list of articles shows it. Writing takes the permission of the news, moderating the one of the
 * news or of all comments; the author removes their own comment.
 */
final readonly class NewsCommentSubject implements CommentSubjectInterface
{
    public const string TYPE = 'news';

    public function __construct(
        private NewsReadServiceInterface $news,
        private NewsCollectionAvailability $availability,
        private AccessCheckerInterface $access,
    ) {
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function find(int $id): ?CommentSubjectDTO
    {
        if (! $this->availability->ready()) {
            return null;
        }

        try {
            $article = $this->news->findById($id);
        } catch (NewsCollectionUnavailableException) {
            return null;
        }
        if ($article === null || $article->url === '') {
            return null;
        }

        return $this->forArticle($article);
    }

    /** The discussion of an article the page has already read. */
    public function forArticle(NewsArticleViewDTO $article): CommentSubjectDTO
    {
        $breadcrumbs = [['name' => d__('news', 'News'), 'url' => '/news/']];
        if ($article->record->sectionId !== null) {
            foreach ($this->news->breadcrumbs($article->record->sectionId) as $crumb) {
                $breadcrumbs[] = ['name' => $crumb->record->name, 'url' => $crumb->url];
            }
        }
        $breadcrumbs[] = ['name' => $article->record->name, 'url' => $article->url];

        return new CommentSubjectDTO(
            type: self::TYPE,
            id: $article->record->id,
            title: $article->record->name,
            url: $article->url,
            discussionUrl: '/news/comments/' . $article->record->id,
            open: $this->access->allows(NewsPermissions::COMMENTS_POST),
            breadcrumbs: $breadcrumbs,
            description: $article->metaDescription,
            embeddedIn: $article->url,
            moderator: $this->access->allows(NewsPermissions::COMMENTS_MODERATE),
            authorCanDelete: true,
        );
    }

    public function hiddenIds(): ?array
    {
        if (! $this->availability->ready()) {
            return null;
        }

        // The articles find() does not find: the feeds leave their comments out before they are
        // paginated and counted.
        try {
            return $this->news->hiddenIds();
        } catch (NewsCollectionUnavailableException) {
            return null;
        }
    }

    public function commentsChanged(int $id, int $delta): void
    {
        // The article keeps no count of its own.
    }

    public function discussionViewed(CommentSubjectDTO $subject): void
    {
        // Nothing is marked as unread for the author of an article.
    }
}
