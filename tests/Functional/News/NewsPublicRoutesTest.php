<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Http\Kernel;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Security\Csrf;
use Johncms\Updates\SystemUpdateStateInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class NewsPublicRoutesTest extends FunctionalTestCase
{
    private int $collection;
    private CollectionsApiInterface $api;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container()->get(CollectionDeclarationsSynchronizer::class)->synchronize();
        $this->collection = $this->container()->get(NewsReadServiceInterface::class)->collection()->id;
        $this->api = $this->container()->get(CollectionsApiInterface::class);
    }

    public function testRoutesResolveOwnSectionArticleAndSharePublicationAndExactTagRules(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $a = $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'a', 'A', null, true, 100));
        $b = $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'b', 'B', null, true, 100));
        $first = $this->item('First body', $a, 'same', ['php']);
        $this->item('Second body', $b, 'same', ['phpstorm']);
        $this->item(body: 'Draft body', code: 'draft', active: false);
        self::assertStringContainsString('First body', (string) $this->handleRequest('/news/a/same.html')->getContent());
        $second = HTMLDocument::createFromString((string) $this->handleRequest('/news/b/same.html')->getContent(), LIBXML_NOERROR)
            ->querySelector('.site-news-article__body')->textContent;
        self::assertStringContainsString('Second body', $second);
        self::assertStringNotContainsString('First body', $second, 'The same code in another section is another article.');
        self::assertSame(404, $this->handleRequest('/news/same.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/news/draft.html')->getStatusCode());
        $tags = (string) $this->handleRequest('/news/search_tags?tag=PHP')->getContent();
        self::assertStringContainsString('/news/a/same.html', $tags);
        self::assertStringNotContainsString('/news/b/same.html', $tags);
        $this->container()->get(CollectionSearchIndexer::class)->rebuildCollection($this->collection);
        $search = (string) $this->handleRequest('/news/search?query=First%20body')->getContent();
        self::assertStringContainsString('/news/a/same.html', $search);
        $this->handleRequest('/news/a/same.html');
        self::assertSame(1, $this->api->getItem($first)->viewCount);
        Capsule::table('collection_sections')->where('id', $a)->update(['active' => false]);
        self::assertSame(404, $this->handleRequest('/news/a/same.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/news/a/')->getStatusCode());
    }

    public function testArchiveKeepsSectionDescriptionsPreviewLinksAndDiscussionAnchors(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $section = $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'releases', 'Releases', '<p>Release notes</p>', true, 100, [], 'Every version & patch'));
        $this->api->addItem(new CollectionItemFormDTO(
            collectionId: $this->collection,
            sectionId: $section,
            code: 'release',
            name: 'A release',
            active: true,
            activeFrom: null,
            activeTo: null,
            sort: 100,
            previewText: '<p>Read <a href="https://example.com/notes">the notes</a></p>',
            detailText: null,
            values: [],
        ));
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/')->getContent(), LIBXML_NOERROR);
        $card = $document->querySelector('a.site-news-section[href="/news/releases/"]');
        self::assertNotNull($card, 'The whole section card leads to the section.');
        self::assertSame('Releases', trim($card->querySelector('.site-news-section__title')->textContent));
        self::assertSame('Every version & patch', trim($card->querySelector('.site-news-section__summary')->textContent), 'The card shows the short description, not the long text.');
        self::assertSame('1', trim($card->querySelector('.site-news__count')->textContent));
        self::assertNotNull($card->querySelector('.site-news-section__last'), 'The card tells when the section was last updated.');
        self::assertStringContainsString(sprintf(dn__('news', '%d publication', '%d publications', 1), 1), $document->querySelector('.site-page-stat')->textContent);
        self::assertNotNull($document->querySelector('.site-news-card__preview a[href="https://example.com/notes"]'));
        self::assertNotNull($document->querySelector('.site-news-card a[href="/news/releases/release.html#article-comments"]'));
        self::assertNull($document->querySelector('.site-news-card > a'), 'Rich previews must not be wrapped in an article link.');
        self::assertNotNull($document->querySelector('.site-news-card__title a.stretched-link[href="/news/releases/release.html"]'), 'The title link makes the whole card clickable.');
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/releases/')->getContent(), LIBXML_NOERROR);
        self::assertStringContainsString('Every version & patch', $document->querySelector('.site-page-heading__subtitle')->textContent);
        self::assertStringContainsString('Release notes', $document->querySelector('.site-news__description')->textContent);
        self::assertStringNotContainsString('Every version', $document->querySelector('.site-news__description')->textContent);
    }

    public function testSectionWithoutShortDescriptionShowsNoSummary(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'long', 'Long', '<p>Only the long text</p>', true, 100));
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/')->getContent(), LIBXML_NOERROR);
        $card = $document->querySelector('a.site-news-section[href="/news/long/"]');
        self::assertNotNull($card);
        self::assertNull($card->querySelector('.site-news-section__summary'), 'The long text is never cut down into a card.');
    }

    public function testArticleHasOneHeadingTagsAndAnEditLinkOnlyForEditors(): void
    {
        $id = $this->item(body: 'Article body', tags: ['cms']);
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html')->getContent(), LIBXML_NOERROR);
        self::assertSame(1, $document->querySelectorAll('h1')->length);
        self::assertSame('Article body', $document->querySelector('.site-breadcrumbs [aria-current="page"]')->textContent);
        self::assertNotNull($document->querySelector('.site-breadcrumbs a[href="/news/"]'));
        self::assertNotNull($document->querySelector('.site-news-article__tags a[href="/news/search_tags/?tag=cms"]'));
        self::assertNotNull($document->querySelector('a[href="#article-comments"]'));
        self::assertNull($document->querySelector('.site-news-article__edit'));
        self::assertNull($document->querySelector('[data-editor]'), 'A guest gets no comment form.');
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent(), LIBXML_NOERROR);
        self::assertSame('/admin/collections/' . $this->collection . '/items/' . $id . '/edit', $document->querySelector('.site-news-article__edit')->getAttribute('href'));
        self::assertNotNull($document->querySelector('#comment-form [data-editor] textarea[name="text"]'));
    }

    public function testArticleOffersItsSectionFirstThenTheSiteAndThePopularNotShownAbove(): void
    {
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'releases', 'name' => 'Releases', 'active' => 1]);
        $this->item(body: 'Current', section: $section, code: 'current');
        $this->item(body: 'Neighbour', section: $section, code: 'neighbour');
        foreach (['first', 'second', 'third', 'fourth'] as $views => $code) {
            $id = $this->item(body: ucfirst($code), code: $code);
            Capsule::table('collection_items')->where('id', $id)->update(['view_count' => $views, 'created_at' => '2020-01-0' . ($views + 1) . ' 00:00:00']);
        }
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/releases/current.html')->getContent(), LIBXML_NOERROR);
        $links = static fn (string $panel): array => array_map(
            static fn ($link): string => $link->getAttribute('href'),
            iterator_to_array($document->querySelectorAll('[aria-labelledby="' . $panel . '"] a')),
        );
        self::assertSame(['/news/releases/neighbour.html', '/news/fourth.html', '/news/third.html'], $links('news-related-title'), 'The section goes first, the latest news of the site fill the rest.');
        self::assertSame(['/news/second.html', '/news/first.html'], $links('news-popular-title'), 'Popular news skip the article and what is offered above.');
        self::assertNotNull($document->querySelector('.site-news-article__section[href="/news/releases/"]'));
    }

    public function testCommentsArePostedAsFormsAndRenderedWithThePage(): void
    {
        $id = $this->item('Public body');
        $user = FunctionalUserFactory::create();
        $response = $this->postForm('/news/comments/add/' . $id, ['text' => '<b>Comment</b>', 'user_id' => '999', 'user_data' => ['ip' => 'fake']], $user);
        $comment = (int) Capsule::table('news_comments')->where('item_id', $id)->value('id');
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/news/entry.html#comment-' . $comment, $response->headers->get('Location'));
        self::assertSame($user->id, (int) Capsule::table('news_comments')->where('id', $comment)->value('user_id'), 'The author is the signed-in user, never a posted field.');

        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html')->getContent(), LIBXML_NOERROR);
        $rendered = $document->querySelector('#comment-' . $comment);
        self::assertNotNull($rendered, 'Comments are part of the page, not loaded afterwards.');
        self::assertSame('Comment', trim($rendered->querySelector('.site-news-comment__body')->textContent));
        self::assertSame('#1', trim($rendered->querySelector('.site-news-comment__anchor')->textContent));
        self::assertNull($rendered->querySelector('.site-news-comment__ip'), 'Only moderators see the address.');
        self::assertNull($rendered->querySelector('form[action="/news/comments/del/"]'), 'A guest cannot delete.');
        $moderated = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent(), LIBXML_NOERROR);
        self::assertNotNull($moderated->querySelector('#comment-' . $comment . ' .site-news-comment__ip'));

        $empty = $this->postForm('/news/comments/add/' . $id, ['text' => '  '], $user);
        self::assertSame('/news/entry.html#comment-form', $empty->headers->get('Location'), 'A rejected comment goes back to the form.');
        self::assertSame(1, Capsule::table('news_comments')->count());

        $deleted = $this->postForm('/news/comments/del/', ['comment_id' => (string) $comment, 'article_id' => (string) $id, 'page' => '1'], $user);
        self::assertSame('/news/entry.html#article-comments', $deleted->headers->get('Location'));
        self::assertSame(0, Capsule::table('news_comments')->whereNull('deleted_at')->count());
        Capsule::table('collection_items')->where('id', $id)->update(['active' => false]);
        self::assertSame(404, $this->handleRequest('/news/entry.html')->getStatusCode());
    }

    public function testCommentPagesOpenOnTheDiscussion(): void
    {
        $id = $this->item('Long body');
        $user = FunctionalUserFactory::create();
        for ($i = 1; $i <= 11; $i++) {
            $this->postForm('/news/comments/add/' . $id, ['text' => 'Comment ' . $i], $user);
        }

        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html')->getContent(), LIBXML_NOERROR);
        self::assertNotNull(
            $document->querySelector('.site-news-comments__pagination a[href="/news/entry.html?page=2#article-comments"]'),
            'A page of comments opens on the discussion, not on the top of the article.',
        );
        self::assertSame('/news/entry.html?page=2#article-comments', $this->handleRequest('/news/entry.html?page=9')->headers->get('Location'));

        $views = $this->api->getItem($id)->viewCount;
        // A view is counted once per session: forget it, so that only the kind of request decides.
        $this->container()->get(Session::class)->remove('news_viewed_items');
        $fragment = $this->handleRequest('/news/entry.html?page=2', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $document = HTMLDocument::createFromString('<!doctype html><body>' . $fragment->getContent(), LIBXML_NOERROR);
        $region = $document->querySelector('[data-live-list-region="comments"]');
        self::assertNotNull($region, 'The live list gets the region of the discussion.');
        self::assertSame('#11', trim($region->querySelector('.site-news-comment__anchor')->textContent));
        self::assertNull($document->querySelector('.site-news-article__body'), 'The fragment holds the discussion only.');
        self::assertStringStartsWith('Long body — ' . d__('system', 'Page') . ' 2 | ', (string) $document->querySelector('title')?->textContent, 'The title follows the page of comments.');
        self::assertSame('11', trim((string) $document->querySelector('[data-live-list-region="comments-stat"]')?->textContent), 'The count in the header of the article follows the discussion.');
        self::assertSame($views, $this->api->getItem($id)->viewCount, 'Another page of comments is not another view of the article.');
        $pageField = $region->querySelector('input[name="page"]');
        self::assertSame('2', $pageField?->getAttribute('value'));
        self::assertSame('news-comment-form-' . $id, $pageField->getAttribute('form'));

        $response = $this->postForm('/news/comments/add/' . $id, ['text' => '<p>&nbsp;</p>', 'page' => '2'], $user);
        self::assertSame('/news/entry.html?page=2#comment-form', $response->headers->get('Location'));
        $document = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html?page=2', cookies: $this->actingAs($user))->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('#comment-form .alert-danger'));
        self::assertSame('#11', trim($document->querySelector('.site-news-comment__anchor')->textContent));
        self::assertNotNull($document->querySelector('form#news-comment-form-' . $id));

        $this->postForm('/news/comments/add/' . $id, ['text' => '<p>&nbsp;</p>', 'page' => '2'], $user);
        $fragment = $this->fragment('/news/entry.html?page=2', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        self::assertNotNull($fragment->querySelector('[data-live-list-region="comment-error"] .alert-danger'));
        self::assertSame('2', $fragment->querySelector('[data-live-list-region="comments"] input[name="page"]')?->getAttribute('value'));

    }

    public function testTheLiveListLearnsTheResultOfACommentFromThePageAfterTheForm(): void
    {
        $id = $this->item('Public body');
        $user = FunctionalUserFactory::create();
        $live = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        $this->postForm('/news/comments/add/' . $id, ['text' => 'Live comment'], $user);
        $comment = (int) Capsule::table('news_comments')->where('item_id', $id)->value('id');
        $fragment = $this->fragment('/news/entry.html', $live);
        self::assertSame('comment-' . $comment, $fragment->querySelector('[data-live-list-target]')?->getAttribute('id'), 'The comment just added is the one to bring into view.');
        self::assertNull($fragment->querySelector('[data-live-list-region="comment-error"] .alert'));
        self::assertNotNull($fragment->querySelector('[data-toast="success"][data-toast-announce]'), 'The comment shows itself: the message is only read out.');
        self::assertNull($this->fragment('/news/entry.html', $live)->querySelector('[data-live-list-target]'), 'Only the page right after the form marks it.');

        $this->postForm('/news/comments/del/', ['comment_id' => (string) $comment, 'article_id' => (string) $id, 'page' => '1'], FunctionalUserFactory::create());
        $fragment = $this->fragment('/news/entry.html', $live);
        self::assertNotNull($fragment->querySelector('[data-toast="error"]'), 'A refused deletion is a toast: whoever deletes may have no form.');
        self::assertNull($fragment->querySelector('[data-live-list-region="comment-error"] .alert'));
        $this->postForm('/news/comments/del/', ['comment_id' => (string) $comment, 'article_id' => (string) $id, 'page' => '1'], $user);
        $toast = $this->fragment('/news/entry.html', $live)->querySelector('[data-toast="success"]');
        self::assertNotNull($toast);
        self::assertFalse($toast->hasAttribute('data-toast-announce'), 'A deleted comment leaves nothing to show the result: the toast does.');

        $this->postForm('/news/comments/add/' . $id, ['text' => '  '], $user);
        $fragment = $this->fragment('/news/entry.html', $live);
        self::assertNotNull($fragment->querySelector('[data-live-list-region="comment-error"] .alert-danger'), 'A rejected comment is reported beside the form.');
        self::assertNull($fragment->querySelector('[data-live-list-target]'));
        $page = HTMLDocument::createFromString((string) $this->handleRequest('/news/entry.html', cookies: $this->actingAs($user))->getContent(), LIBXML_NOERROR);
        self::assertNull($page->querySelector('#comment-form .alert-danger'), 'The error was shown by the live list, not again on the next page.');
    }

    public function testVoteHttpContractUsesCollectionIdsAndCurrentActor(): void
    {
        $id = $this->item('Public body');
        $cookies = $this->actingAs(FunctionalUserFactory::create());
        $vote = $this->json('/news/add_vote/' . $id . '/1', [], $cookies);
        self::assertSame(200, $vote->getStatusCode());
        self::assertSame(1, json_decode((string) $vote->getContent(), true)['rating']);
        self::assertSame(404, $this->handleRequest('/news/add_vote/' . $id . '/1')->getStatusCode());
        self::assertSame(1, Capsule::table('news_votes')->count());
        Capsule::table('collection_items')->where('id', $id)->update(['active' => false]);
        self::assertSame(404, $this->json('/news/add_vote/' . $id . '/1', [], $cookies)->getStatusCode());
    }

    public function testVoteWidgetSendsTheRequestItsRouteAccepts(): void
    {
        $id = $this->item('Public body');
        $cookies = $this->actingAs(FunctionalUserFactory::create());
        self::assertStringContainsString('axios.post(this.set_vote_url + this.article_id', (string) file_get_contents(ROOT_PATH . 'themes/default/src/js/components/LikesComponent.vue'));
        $vote = $this->json('/news/add_vote/' . $id . '/1/', [], $cookies);
        self::assertSame(200, $vote->getStatusCode());
        self::assertSame(1, json_decode((string) $vote->getContent(), true)['rating']);
    }

    public function testSectionCodesWithDotsOpenTheSectionAndItsArticles(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $top = $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'v1.0', 'Version', null, true, 100));
        $nested = $sections->addSection(new CollectionSectionFormDTO($this->collection, $top, 'rc.1', 'Candidate', null, true, 100));
        $this->item('Nested body', $nested, 'notes.final');
        $this->item(body: 'Root body', code: 'root.entry');
        self::assertSame(200, $this->handleRequest('/news/v1.0/')->getStatusCode());
        self::assertSame(200, $this->handleRequest('/news/v1.0/rc.1/')->getStatusCode());
        self::assertStringContainsString('Nested body', (string) $this->handleRequest('/news/v1.0/rc.1/notes.final.html')->getContent());
        self::assertStringContainsString('Root body', (string) $this->handleRequest('/news/root.entry.html')->getContent());
        self::assertSame(404, $this->handleRequest('/news/missing.section/')->getStatusCode());
    }

    public function testMalformedAddressesAndSearchTermsAreNotServerErrors(): void
    {
        $sections = $this->container()->get(CollectionSectionsApiInterface::class);
        $sections->addSection(new CollectionSectionFormDTO($this->collection, null, 'a', 'A', null, true, 100));
        $this->item(body: 'Matching body', code: 'matching');
        $this->container()->get(CollectionSearchIndexer::class)->rebuildCollection($this->collection);
        self::assertSame(404, $this->handleRequest('/news/a//b/')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/news/a//b/entry.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/news/a/../entry.html')->getStatusCode());
        foreach (['%C2%A0', '%FF'] as $empty) {
            $response = $this->handleRequest('/news/search?query=' . $empty);
            self::assertSame(200, $response->getStatusCode());
            self::assertStringNotContainsString('/news/matching.html', (string) $response->getContent());
        }
        $long = $this->handleRequest('/news/search?query=' . str_repeat('a', 300));
        self::assertSame(200, $long->getStatusCode());
        self::assertStringContainsString('/news/matching.html', (string) $this->handleRequest('/news/search?query=matching')->getContent());
    }

    public function testPendingUpdateBlocksPublicRoutesUploadsAndAllCollectionWrites(): void
    {
        $id = $this->item('Public body');
        $state = $this->container()->get(SystemUpdateStateInterface::class);
        $state->begin('johncms/forum');
        self::assertSame(200, $this->handleRequest('/news/')->getStatusCode());
        $state->begin('johncms/news');
        try {
            self::assertSame(503, $this->handleRequest('/news/')->getStatusCode());
            self::assertSame(503, $this->handleRequest('/news/entry.html')->getStatusCode());
            $editor = FunctionalUserFactory::createSupervisor();
            self::assertSame(403, $this->handleRequest('/admin/collections/' . $this->collection . '/items', cookies: $this->actingAs($editor))->getStatusCode());
            try {
                $this->api->deleteItem($id);
                self::fail('Trusted PHP writers must also respect an unfinished news update outside its system context.');
            } catch (CollectionAccessDeniedException) {
                self::assertNotNull($this->api->getItem($id));
            }
        } finally {
            $state->complete();
        }
        self::assertSame(200, $this->handleRequest('/news/entry.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/admin/news', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getStatusCode());
    }

    /** @param list<string> $tags */
    private function item(string $body, ?int $section = null, string $code = 'entry', array $tags = [], bool $active = true): int
    {
        return $this->api->addItem(new CollectionItemFormDTO($this->collection, $section, $code, $body, $active, null, null, 100, null, $body, ['tags' => $tags]));
    }

    /** @param array<string, string> $server */
    private function fragment(string $url, array $server): HTMLDocument
    {
        return HTMLDocument::createFromString('<!doctype html><body>' . $this->handleRequest($url, server: $server)->getContent(), LIBXML_NOERROR);
    }

    /** @param array<string, mixed> $body
     * @param array<string, string> $cookies
     */
    private function json(string $url, array $body, array $cookies): Response
    {
        $request = Request::create($url, 'POST', [], $cookies, [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $this->container()->get(Csrf::class)->getToken()], json_encode($body, JSON_THROW_ON_ERROR));
        return $this->container()->get(Kernel::class)->handle($request);
    }
}
