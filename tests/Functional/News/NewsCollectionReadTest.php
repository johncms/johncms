<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionPreviewAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\View\RendererInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class NewsCollectionReadTest extends FunctionalTestCase
{
    private int $collection;
    private CollectionsApiInterface $api;
    private NewsReadServiceInterface $news;

    protected function setUp(): void
    {
        parent::setUp();
        // Direct reader calls have no HTTP request and must start with a fresh guest identity.
        $this->container()->get(CurrentUser::class)->reset();
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $this->api = $this->container()->get(CollectionsApiInterface::class);
        $this->news = $this->container()->get(NewsReadServiceInterface::class);
        $this->collection = $this->news->collection()->id;
    }

    public function testListsDetailsSubtreesSearchAndExactTagsReadTheCollection(): void
    {
        $top = $this->section('top');
        $child = $this->section('child', $top);
        $first = $this->item(name: 'First', section: $child, tags: [' PHP ', 'php']);
        $second = $this->item(name: 'Second', tags: ['phpstorm']);
        $page = $this->news->page(new NewsQueryDTO(sectionId: $top));
        self::assertSame(1, $page->total);
        self::assertSame($first, $page->articles[0]->record->id);
        self::assertSame('/news/top/child/first.html', $page->articles[0]->url);
        self::assertSame(['php'], $page->articles[0]->tags);
        self::assertSame(2, $this->news->count());
        self::assertSame(0, $this->news->count(new NewsQueryDTO(sectionId: $top, includeSubsections: false)));
        self::assertSame($first, $this->news->page(new NewsQueryDTO(tag: ' PHP '))->articles[0]->record->id);
        self::assertSame(1, $this->news->page(new NewsQueryDTO(search: 'Body First'))->total);
        self::assertSame($first, $this->news->findByCode('top/child', 'first')->record->id);
        self::assertSame($second, $this->news->findByCode('', 'second')->record->id);
        self::assertSame(['top', 'child'], array_map(static fn ($section) => $section->record->code, $this->news->breadcrumbs($child)));
        self::assertSame($top, $this->news->sections()[0]->record->id);
        self::assertSame($child, $this->news->sections($top)[0]->record->id);
        self::assertSame($child, $this->news->resolveSection('top/child/')->record->id);
        self::assertNull($this->news->findByCode('top', 'first'));
        self::assertSame(0, Capsule::table('news_articles')->count());
    }

    public function testPreparedViewsRenderExistingTemplatesWithoutModelsOrAnAuthor(): void
    {
        $id = $this->item(name: 'No author', values: ['page_title' => '<b>SEO title</b>', 'description' => '<p>SEO description</p>', 'keywords' => 'cms, php']);
        $article = $this->news->findById($id);
        self::assertNull($article->author);
        self::assertSame(0, $article->stats->rating);
        self::assertSame(0, $article->stats->comments);
        self::assertSame('SEO title', $article->metaTitle);
        $html = $this->container()->get(RendererInterface::class)->render('@news/public/article.twig', $article->metadata() + [
            'article' => $article->toTemplate(),
            'current_section' => null,
            'related' => [],
            'popular' => [],
            'comments' => [],
            'comments_total' => 0,
            'comments_offset' => 0,
            'comments_page' => 1,
            'comments_pagination' => null,
            'comment_error' => null,
            'comment_message' => null,
            'comment_text' => '',
            'author_articles' => 0,
            'can_write' => false,
            'edit_url' => null,
        ]);
        self::assertStringContainsString('Body No author', $html);
        self::assertStringNotContainsString('<script>bad()</script>', $html);
        self::assertStringContainsString('name="description" content="SEO description"', $html);
        self::assertStringContainsString(':article_id="' . $id . '"', $html);
        $list = $this->container()->get(RendererInterface::class)->render('@news/homepage/latest.twig', ['news' => [$article->toTemplate()]]);
        self::assertStringContainsString('/news/no-author.html#article-comments', $list);
    }

    public function testVisibilityIsSharedByDetailsSearchSitemapAndSections(): void
    {
        $top = $this->section('top');
        $child = $this->section('child', $top);
        $id = $this->item(name: 'Hidden branch', section: $child, tags: ['private-tag']);
        Capsule::table('collection_sections')->where('id', $top)->update(['active' => false]);
        self::assertNull($this->news->findById($id));
        self::assertSame(0, $this->news->count(new NewsQueryDTO(search: 'Body')));
        self::assertSame([], $this->news->sections());
        self::assertSame([], iterator_to_array($this->news->sitemap('https://example.test')));
        try {
            $this->news->resolveSection('top/child');
            self::fail('Hidden section paths must not resolve.');
        } catch (NewsItemNotFoundException) {
            self::assertNull($this->news->section($child));
        }
        Capsule::table('collection_sections')->where('id', $top)->update(['active' => true]);
        Capsule::table('collection_items')->where('id', $id)->update(['active_from' => '2099-01-01 00:00:00']);
        self::assertNull($this->news->findById($id));
        self::assertSame(0, $this->news->count());
        Capsule::table('collections')->where('id', $this->collection)->update(['active' => false]);
        self::assertSame([], iterator_to_array($this->news->sitemap('https://example.test')));
    }

    public function testPreviewRequiresContentPermissionAndDoesNotCountViews(): void
    {
        $id = $this->item(name: 'Draft', active: false);
        $user = FunctionalUserFactory::createWithPermissions(['news.manage']);
        $this->handleRequest('/news/', cookies: $this->actingAs($user));
        self::assertSame($id, $this->news->preview($id)->record->id);
        self::assertNull($this->news->findById($id));
        self::assertSame(0, $this->api->getItem($id)->viewCount);
        $this->handleRequest('/news/');
        $this->expectException(CollectionPreviewAccessDeniedException::class);
        $this->news->preview($id);
    }

    public function testViewsAreCountedOncePerSessionAndProjectionOmitsLargeTexts(): void
    {
        $id = $this->item('Viewed');
        $this->news->registerView($id);
        $this->news->registerView($id);
        self::assertSame(1, $this->api->getItem($id)->viewCount);
        $projected = $this->api->getItems(new ContentCollectionItemQuery(collectionId: $this->collection, fields: [], includeText: false))[0];
        self::assertNull($projected->detailText);
        self::assertNull($projected->previewText);
        self::assertSame('Viewed', $projected->name);
        Capsule::table('collection_items')->where('id', $id)->update(['active' => false]);
        $this->expectException(NewsItemNotFoundException::class);
        $this->news->registerView($id);
    }

    public function testHomepageNewCounterAndSitemapUseTheSamePublicationWindow(): void
    {
        $recent = $this->item('Recent');
        $old = $this->item('Old');
        $draft = $this->item(name: 'Draft', active: false);
        Capsule::table('collection_items')->where('id', $old)->update(['active_from' => '2001-01-01 00:00:00']);
        $this->withConfig(['news' => ['homepage_show' => true, 'homepage_days' => 3, 'homepage_quantity' => 1]], function () use ($recent): void {
            self::assertSame([$recent], array_map(static fn ($article) => $article->record->id, $this->news->homepage()));
            self::assertSame(1, $this->news->countNew(3));
            self::assertSame(0, $this->news->countNew(0));
        });
        $urls = array_map(static fn ($entry) => $entry->loc, iterator_to_array($this->news->sitemap('https://example.test')));
        self::assertContains('https://example.test/news/recent.html', $urls);
        self::assertContains('https://example.test/news/old.html', $urls);
        self::assertNotContains('https://example.test/news/draft.html', $urls);
    }

    public function testArticleAuthorsAndDiscussionCountersAreLoadedInBatches(): void
    {
        $author = FunctionalUserFactory::create();
        for ($i = 0; $i < 8; ++$i) {
            $id = $this->item(name: 'Batch ' . $i, author: $author->id);
            Fixture::insert('news_comments', ['article_id' => null, 'item_id' => $id, 'user_id' => $author->id, 'text' => 'Comment', 'user_data' => '{}', 'created_at' => gmdate('Y-m-d H:i:s')]);
            Fixture::insert('news_votes', ['article_id' => null, 'item_id' => $id, 'user_id' => $author->id, 'vote' => 1]);
        }
        $connection = Capsule::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        try {
            $page = $this->news->page();
            $queries = array_column($connection->getQueryLog(), 'query');
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
        self::assertCount(8, $page->articles);
        self::assertCount(1, array_filter($queries, static fn (string $query): bool => str_contains($query, 'news_comments')));
        self::assertCount(1, array_filter($queries, static fn (string $query): bool => str_contains($query, 'news_votes')));
        self::assertCount(1, array_filter($queries, static fn (string $query): bool => preg_match('/from [`"]?users[`"]? where/i', $query) === 1));
        foreach ($page->articles as $article) {
            self::assertSame($author->id, $article->author->id);
            self::assertSame(1, $article->stats->comments);
            self::assertSame(1, $article->stats->rating);
        }
    }

    public function testListsDoNotReadTheSectionsOncePerRecord(): void
    {
        $parent = $this->section('parent');
        for ($i = 0; $i < 12; ++$i) {
            $this->section('child-' . $i, $parent);
            $this->item('Sectioned ' . $i, $parent);
        }
        // The identity of the visitor is loaded once, by the first read.
        $this->news->page(new NewsQueryDTO(limit: 1));
        $connection = Capsule::connection();
        $count = function (callable $read) use ($connection): int {
            $connection->flushQueryLog();
            $connection->enableQueryLog();
            try {
                $read();
                return count($connection->getQueryLog());
            } finally {
                $connection->disableQueryLog();
                $connection->flushQueryLog();
            }
        };
        self::assertSame(
            $count(fn () => $this->news->page(new NewsQueryDTO(sectionId: $parent, limit: 2))),
            $count(fn () => $this->news->page(new NewsQueryDTO(sectionId: $parent, limit: 12)))
        );
        self::assertCount(12, $this->news->sections($parent));
        self::assertLessThan(12, $count(fn () => $this->news->sections($parent)));
        self::assertSame('/news/parent/child-0/', $this->news->sections($parent)[0]->url);
    }

    private function section(string $code, ?int $parent = null): int
    {
        return $this->container()->get(CollectionSectionsApiInterface::class)->addSection(new CollectionSectionFormDTO($this->collection, $parent, $code, ucfirst($code), '<p>Section body</p>', true, 100));
    }

    private function item(string $name, ?int $section = null, array $tags = [], array $values = [], bool $active = true, ?int $author = null): int
    {
        $code = strtolower(str_replace(' ', '-', $name));
        $id = $this->api->addItem(new CollectionItemFormDTO($this->collection, $section, $code, $name, $active, null, null, 100, '<p>Lead</p>', '<p>Body ' . $name . '</p><script>bad()</script>', ['tags' => $tags] + $values), $author);
        $this->container()->get(CollectionSearchIndexer::class)->rebuildItem($this->collection, $id);
        return $id;
    }
}
