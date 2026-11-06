<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\RolePermission;
use Johncms\Auth\CurrentUser;
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
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class NewsCollectionDiscussionTest extends FunctionalTestCase
{
    private int $collection;
    private int $item;
    private CollectionsApiInterface $api;
    private NewsDiscussionServiceInterface $discussion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $this->api = $this->container()->get(CollectionsApiInterface::class);
        $this->collection = $this->api->findDeclaredCollection('johncms/news', 'articles')->id;
        $this->item = $this->api->addItem(new CollectionItemFormDTO($this->collection, null, 'entry', 'Entry', true, null, null, 100, null, '<p>Body</p>', []));
        $this->discussion = $this->container()->get(NewsDiscussionServiceInterface::class);
    }

    public function testCommentsAndVotesUseItemIdAndTheCurrentActorWithBatchStats(): void
    {
        $user = FunctionalUserFactory::createWithPermissions(['news.comments.post', 'news.vote']);
        $this->asUser($user);
        $comment = $this->discussion->addComment(itemId: $this->item, text: '<p>Hello</p><script>bad()</script>', metadata: ['ip' => '127.0.0.1']);
        self::assertNull(Capsule::table('news_comments')->where('id', $comment)->value('article_id'));
        self::assertSame($this->item, Capsule::table('news_comments')->where('id', $comment)->value('item_id'));
        self::assertSame($user->id, Capsule::table('news_comments')->where('id', $comment)->value('user_id'));
        self::assertSame('<p>Hello</p><script>bad()</script>', Capsule::table('news_comments')->where('id', $comment)->value('text'));
        $page = $this->discussion->comments($this->item);
        self::assertSame(1, $page->total);
        self::assertStringContainsString('Hello', (string) $page->comments[0]->text);
        self::assertStringNotContainsString('bad()', (string) $page->comments[0]->text);
        self::assertSame([], $page->comments[0]->moderatorData);
        self::assertTrue($page->comments[0]->canDelete);
        self::assertSame(1, $this->discussion->vote($this->item, 1)->rating);
        self::assertSame(-1, $this->discussion->vote($this->item, -1)->rating);
        self::assertSame(1, Capsule::table('news_votes')->where('item_id', $this->item)->count());
        self::assertNull(Capsule::table('news_votes')->where('item_id', $this->item)->value('article_id'));
        $article = $this->container()->get(NewsReadServiceInterface::class)->findById($this->item);
        self::assertSame(1, $article->stats->comments);
        self::assertSame(-1, $article->stats->rating);
        self::assertSame(-1, $article->stats->currentVote);
    }

    public function testUnavailableAndForeignItemsRejectReadingAndWritingDiscussion(): void
    {
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.post', 'news.vote', 'news.manage']));
        $foreign = Fixture::insert('collections', ['code' => 'foreign', 'name' => 'Foreign', 'active' => 1]);
        $id = $this->api->addItem(new CollectionItemFormDTO($foreign, null, 'entry', 'Foreign', true, null, null, 100, null, null, []));
        Capsule::table('collection_items')->where('id', $this->item)->update(['active' => false]);
        foreach ([$id, $this->item] as $target) {
            foreach ([fn () => $this->discussion->comments($target), fn () => $this->discussion->addComment($target, 'Forged'), fn () => $this->discussion->vote($target, 1)] as $action) {
                try {
                    $action();
                    self::fail('Unavailable or foreign items cannot expose a discussion.');
                } catch (NewsItemNotFoundException) {
                    self::assertSame(0, Capsule::table('news_comments')->count());
                    self::assertSame(0, Capsule::table('news_votes')->count());
                }
            }
        }
    }

    public function testCommentAndVotePermissionsAreIndependentAndGuestsCanOnlyRead(): void
    {
        RolePermission::query()->whereIn('permission', ['news.comments.post', 'news.vote'])->delete();
        $user = FunctionalUserFactory::createWithPermissions(['news.vote']);
        $this->asUser($user);
        self::assertSame(1, $this->discussion->vote($this->item, 1)->rating);
        try {
            $this->discussion->addComment($this->item, 'Forbidden');
            self::fail('A voter is not automatically allowed to comment.');
        } catch (NewsDiscussionAccessDeniedException) {
            self::assertSame(0, $this->discussion->comments($this->item)->total);
        }
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.post']));
        $this->discussion->addComment($this->item, 'Allowed comment');
        try {
            $this->discussion->vote($this->item, 1);
            self::fail('Voting requires its declared permission.');
        } catch (NewsDiscussionAccessDeniedException) {
            self::assertSame(1, Capsule::table('news_votes')->count());
        }
        $this->handleRequest('/news/');
        $page = $this->discussion->comments($this->item);
        self::assertSame(1, $page->total);
        self::assertFalse($page->comments[0]->canDelete);
        self::assertFalse($page->comments[0]->canReply);
        $this->expectException(NewsDiscussionAccessDeniedException::class);
        $this->discussion->addComment($this->item, 'Guest comment');
    }

    public function testModerationControlsDeletionFeedAndPrivateMetadata(): void
    {
        $author = FunctionalUserFactory::createWithPermissions(['news.comments.post']);
        $this->asUser($author);
        $id = $this->discussion->addComment(itemId: $this->item, text: '<p>Comment</p>', metadata: ['ip' => '127.0.0.1']);
        $other = FunctionalUserFactory::createWithPermissions(['news.comments.post']);
        $this->asUser($other);
        self::assertSame([], $this->discussion->latestFeed(10));
        self::assertSame(0, $this->discussion->countFeed());
        try {
            $this->discussion->deleteComment($id);
            self::fail('Another user cannot remove the comment.');
        } catch (NewsDiscussionAccessDeniedException) {
            self::assertSame(1, $this->discussion->comments($this->item)->total);
        }
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.moderate']));
        self::assertSame(1, $this->discussion->countFeed());
        self::assertSame('/news/entry.html#article-comments', $this->discussion->latestFeed(10)[0]->url);
        self::assertSame('127.0.0.1', $this->discussion->comments($this->item)->comments[0]->moderatorData['ip']);
        $this->discussion->deleteComment($id);
        self::assertSame(0, $this->discussion->comments($this->item)->total);
        self::assertNotNull(Capsule::table('news_comments')->where('id', $id)->value('deleted_at'));
    }

    public function testModeratorCanDeleteCommentsOfHiddenArticlesWhileAuthorsCannot(): void
    {
        $author = FunctionalUserFactory::createWithPermissions(['news.comments.post']);
        $this->asUser($author);
        $comment = $this->discussion->addComment($this->item, 'Hidden article comment');
        Capsule::table('collection_items')->where('id', $this->item)->update(['active' => false]);
        try {
            $this->discussion->deleteComment($comment);
            self::fail('Authors cannot bypass article visibility when deleting comments.');
        } catch (NewsItemNotFoundException) {
            self::assertNull(Capsule::table('news_comments')->where('id', $comment)->value('deleted_at'));
        }
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.moderate']));
        self::assertCount(1, $this->discussion->latestFeed(10));
        $this->discussion->deleteComment($comment);
        self::assertNotNull(Capsule::table('news_comments')->where('id', $comment)->value('deleted_at'));
        self::assertSame(0, $this->discussion->countFeed());
    }

    public function testItemAndSubtreeDeletionRemoveDiscussionAndRollbackRestoresIt(): void
    {
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.post', 'news.vote']));
        $comment = $this->discussion->addComment($this->item, 'Keep until commit');
        $this->discussion->vote($this->item, 1);
        Capsule::connection()->beginTransaction();
        $this->api->deleteItem($this->item);
        self::assertSame(0, Capsule::table('news_comments')->where('id', $comment)->count());
        self::assertSame(0, Capsule::table('news_votes')->where('item_id', $this->item)->count());
        Capsule::connection()->rollBack();
        self::assertSame(1, $this->discussion->comments($this->item)->total);
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $section = $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'subtree', 'Subtree', null, true, 100));
        Capsule::table('collection_items')->where('id', $this->item)->update(['section_id' => $section]);
        $sections->deleteSection($this->collection, $section, SectionDeletionMode::DeleteItems);
        self::assertNull($this->api->getItem($this->item));
        self::assertSame(0, Capsule::table('news_comments')->where('id', $comment)->count());
        self::assertSame(0, Capsule::table('news_votes')->where('item_id', $this->item)->count());
    }

    public function testDeletedAuthorsAndInvalidPayloadsAreHandledExplicitly(): void
    {
        $this->asUser(FunctionalUserFactory::createWithPermissions(['news.comments.post', 'news.vote']));
        $id = Fixture::insert('news_comments', ['article_id' => null, 'item_id' => $this->item, 'user_id' => 999999, 'text' => '<p>Unknown author</p>', 'created_at' => gmdate('Y-m-d H:i:s'), 'user_data' => '{}']);
        self::assertNull($this->discussion->comments($this->item)->comments[0]->author);
        foreach ([fn () => $this->discussion->addComment($this->item, ''), fn () => $this->discussion->vote($this->item, 0), fn () => $this->discussion->addComment($this->item, 'Text', [999999])] as $action) {
            try {
                $action();
                self::fail('Invalid discussion payload must be rejected.');
            } catch (NewsDiscussionValidationException) {
                self::assertSame(1, $this->discussion->comments($this->item)->total);
            }
        }
    }

    private function asUser(User $user): void
    {
        $this->handleRequest('/news/', cookies: $this->actingAs($user));
        self::assertSame($user->id, $this->container()->get(CurrentUser::class)->id());
    }
}
