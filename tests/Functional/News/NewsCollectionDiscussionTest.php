<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\RolePermission;
use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\CommentsService;
use Johncms\Comments\Application\DTO\LatestCommentDTO;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionAccessDeniedException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionValidationException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The discussion of a news article: the comments of the core, shown within the page of the
 * article, and the votes the news keep themselves.
 */
final class NewsCollectionDiscussionTest extends FunctionalTestCase
{
    private const string URL = '/news/entry.html';

    private int $collection;
    private int $item;
    private CollectionsApiInterface $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $this->api = $this->container()->get(CollectionsApiInterface::class);
        $this->collection = $this->api->findDeclaredCollection('johncms/news', 'articles')->id;
        $this->item = $this->api->addItem(new CollectionItemFormDTO($this->collection, null, 'entry', 'Entry', true, null, null, 100, null, '<p>Body</p>', []));
    }

    public function testACommentIsWrittenUnderTheArticleAndShownWithinItsPage(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.comments.post']);

        $response = $this->postForm($this->discussionUrl(), ['text' => '<p>Hello</p><script>bad()</script>'], $user);

        $comment = (int) Capsule::table('comments')->where('subject_type', 'news')->value('id');
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame(self::URL . '#comment-' . $comment, $response->headers->get('Location'));
        self::assertSame($this->item, (int) Capsule::table('comments')->where('id', $comment)->value('subject_id'));
        self::assertSame($user->id, (int) Capsule::table('comments')->where('id', $comment)->value('user_id'));

        $page = $this->page(self::URL, $user);
        $discussion = $page->querySelector('#comments[data-comments]');
        self::assertNotNull($discussion, 'The discussion is a part of the article page.');
        self::assertNotNull($discussion->querySelector('#comment-' . $comment));
        self::assertStringNotContainsString('bad()', $discussion->innerHTML);
        self::assertNotNull($discussion->querySelector('h2.site-discussion__title'), 'Within the article the discussion has a heading of its own.');
        self::assertSame('1', trim((string) $page->querySelector('[data-live-list-region="comments-stat"]')?->textContent));
        self::assertSame($this->discussionUrl(), $discussion->querySelector('form[data-comment-form]')?->getAttribute('action'));
        self::assertSame(1, $this->container()->get(NewsReadServiceInterface::class)->findById($this->item)?->stats->comments);
    }

    public function testTheLiveListGetsTheRegionsOfTheDiscussionAndTheCountOfTheArticle(): void
    {
        $this->comment(FunctionalUserFactory::create()->id);

        $body = (string) $this->handleRequest(self::URL, server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->getContent();

        self::assertStringContainsString('data-live-list-region="comments"', $body);
        self::assertStringContainsString('data-live-list-region="comments-stat"', $body);
        self::assertStringNotContainsString('site-news-article__footer', $body, 'The article itself is not sent again.');
    }

    public function testTheAddressOfTheDiscussionLeadsToTheArticle(): void
    {
        $response = $this->handleRequest($this->discussionUrl() . '?page=2');

        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        self::assertSame(self::URL . '?page=2#comments', $response->headers->get('Location'));
    }

    public function testWritingTakesThePermissionOfTheNewsAndAGuestOnlyReads(): void
    {
        RolePermission::query()->where('permission', 'news.comments.post')->delete();
        $this->comment(FunctionalUserFactory::create()->id);

        $this->postForm($this->discussionUrl(), ['text' => '<p>Forbidden</p>'], FunctionalUserFactory::create());
        $guest = $this->page(self::URL);

        self::assertSame(1, Capsule::table('comments')->where('subject_type', 'news')->count());
        self::assertCount(1, $guest->querySelectorAll('#comments [data-comment]'));
        self::assertNull($guest->querySelector('#comments form[data-comment-form]'));
    }

    public function testTheAuthorDeletesTheirOwnCommentAndNotThatOfSomebodyElse(): void
    {
        $author = FunctionalUserFactory::createWithPermissions(['news.comments.post']);
        $own = $this->comment($author->id);
        $foreign = $this->comment(FunctionalUserFactory::create()->id);

        $this->postForm($this->discussionUrl() . '/delete', ['comment_id' => $foreign, 'confirmed' => '1'], $author);
        $this->postForm($this->discussionUrl() . '/delete', ['comment_id' => $own, 'confirmed' => '1'], $author);

        self::assertFalse(Capsule::table('comments')->where('id', $own)->exists());
        self::assertTrue(Capsule::table('comments')->where('id', $foreign)->exists());
    }

    public function testTheModeratorOfTheNewsDeletesAnyCommentAndSeesWhereItCameFrom(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions(['news.comments.moderate', 'users.origin.view']);
        $id = $this->comment(FunctionalUserFactory::create()->id);

        $page = $this->page(self::URL, $moderator);
        $this->postForm($this->discussionUrl() . '/delete', ['comment_id' => $id, 'confirmed' => '1'], $moderator);

        self::assertNotNull($page->querySelector('#comment-' . $id . ' form[action="' . $this->discussionUrl() . '/delete"]'));
        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testAHiddenOrForeignArticleHasNoDiscussion(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.comments.post']);
        $foreign = Fixture::insert('collections', ['code' => 'foreign', 'name' => 'Foreign', 'active' => 1]);
        $other = $this->api->addItem(new CollectionItemFormDTO($foreign, null, 'entry', 'Foreign', true, null, null, 100, null, null, []));
        Capsule::table('collection_items')->where('id', $this->item)->update(['active' => false]);

        foreach ([$other, $this->item] as $target) {
            $response = $this->postForm('/news/comments/' . $target, ['text' => '<p>Forged</p>'], $user);
            self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        }
        self::assertSame(0, Capsule::table('comments')->count());
    }

    public function testTheFeedLeavesTheCommentsOfHiddenArticlesOutBeforePaging(): void
    {
        $visible = $this->comment(FunctionalUserFactory::create()->id, gmdate('Y-m-d H:i:s', time() - 60));
        $draft = $this->api->addItem(new CollectionItemFormDTO($this->collection, null, 'draft', 'Draft', true, null, null, 100, null, '<p>Body</p>', []));
        $scheduled = $this->api->addItem(new CollectionItemFormDTO($this->collection, null, 'scheduled', 'Scheduled', true, null, null, 100, null, '<p>Body</p>', []));
        Capsule::table('collection_items')->where('id', $draft)->update(['active' => false]);
        Capsule::table('collection_items')->where('id', $scheduled)->update(['active_from' => gmdate('Y-m-d H:i:s', time() + 86400)]);
        // Newer than the visible one: a limit taken before the filter would see only these.
        $this->comment(FunctionalUserFactory::create()->id, subjectId: $draft);
        $this->comment(FunctionalUserFactory::create()->id, subjectId: $scheduled);
        $this->handleRequest('/news/');
        $comments = $this->container()->get(CommentsService::class);

        $latest = $comments->latest('news', 1, 0);

        self::assertSame([$visible], array_map(static fn (LatestCommentDTO $comment): int => $comment->comment->id, $latest));
        self::assertSame(1, $comments->countOfType('news'));
    }

    public function testDeletingTheArticleOrItsSectionTakesItsCommentsAndVotes(): void
    {
        $comment = $this->comment(FunctionalUserFactory::create()->id);
        Fixture::insert('news_votes', ['item_id' => $this->item, 'user_id' => 1, 'vote' => 1]);
        Capsule::connection()->beginTransaction();
        $this->api->deleteItem($this->item);
        self::assertFalse(Capsule::table('comments')->where('id', $comment)->exists());
        self::assertSame(0, Capsule::table('news_votes')->where('item_id', $this->item)->count());
        Capsule::connection()->rollBack();
        self::assertTrue(Capsule::table('comments')->where('id', $comment)->exists());

        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $section = $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'subtree', 'Subtree', null, true, 100));
        Capsule::table('collection_items')->where('id', $this->item)->update(['section_id' => $section]);
        $sections->deleteSection($this->collection, $section, SectionDeletionMode::DeleteItems);

        self::assertNull($this->api->getItem($this->item));
        self::assertFalse(Capsule::table('comments')->where('id', $comment)->exists());
        self::assertSame(0, Capsule::table('news_votes')->where('item_id', $this->item)->count());
    }

    public function testVotesTakeTheirOwnPermissionAndCountOnceForAUser(): void
    {
        RolePermission::query()->where('permission', 'news.vote')->delete();
        $discussion = $this->container()->get(NewsDiscussionServiceInterface::class);
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.vote']));
        self::assertSame(1, $discussion->vote($this->item, 1)->rating);
        self::assertSame(-1, $discussion->vote($this->item, -1)->rating);
        self::assertSame(1, Capsule::table('news_votes')->where('item_id', $this->item)->count());
        $article = $this->container()->get(NewsReadServiceInterface::class)->findById($this->item);
        self::assertNotNull($article);
        self::assertSame(-1, $article->stats->rating);
        self::assertSame(-1, $article->stats->currentVote);

        try {
            $discussion->vote($this->item, 0);
            self::fail('A vote is up or down.');
        } catch (NewsDiscussionValidationException) {
            self::assertSame(1, Capsule::table('news_votes')->count());
        }

        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.post']));
        try {
            $discussion->vote($this->item, 1);
            self::fail('Voting requires its declared permission.');
        } catch (NewsDiscussionAccessDeniedException) {
            self::assertSame(1, Capsule::table('news_votes')->count());
        }

        Capsule::table('collection_items')->where('id', $this->item)->update(['active' => false]);
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.vote']));
        $this->expectException(NewsItemNotFoundException::class);
        $discussion->vote($this->item, 1);
    }

    private function discussionUrl(): string
    {
        return '/news/comments/' . $this->item;
    }

    private function comment(int $userId, ?string $createdAt = null, ?int $subjectId = null): int
    {
        return Fixture::insert('comments', [
            'subject_type' => 'news',
            'subject_id'   => $subjectId ?? $this->item,
            'user_id'      => $userId,
            'text'         => '<p>A comment</p>',
            'author_name'  => 'Author',
            'author_ip'    => '127.0.0.1',
            'created_at'   => $createdAt ?? gmdate('Y-m-d H:i:s'),
        ]);
    }

    private function page(string $url, ?User $user = null): HTMLDocument
    {
        $response = $this->handleRequest($url, cookies: $user === null ? [] : $this->actingAs($user));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    private function asUser(User $user): void
    {
        $this->handleRequest('/news/', cookies: $this->actingAs($user));
        self::assertSame($user->id, $this->container()->get(CurrentUser::class)->id());
    }
}
