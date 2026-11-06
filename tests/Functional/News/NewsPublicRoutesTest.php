<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Http\Kernel;
use Johncms\Http\Request;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Services\CollectionDeclarationsSynchronizer;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Security\Csrf;
use Johncms\Updates\SystemUpdateStateInterface;
use Tests\Functional\FunctionalTestCase;
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
        $second = (string) $this->handleRequest('/news/b/same.html')->getContent();
        self::assertStringContainsString('Second body', $second);
        self::assertStringNotContainsString('First body', $second);
        self::assertSame(404, $this->handleRequest('/news/same.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/news/draft.html')->getStatusCode());
        $tags = (string) $this->handleRequest('/news/search_tags?tag=PHP')->getContent();
        self::assertStringContainsString('/news/a/same.html', $tags);
        self::assertStringNotContainsString('/news/b/same.html', $tags);
        $this->container()->get(\Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer::class)->rebuildCollection($this->collection);
        $search = (string) $this->handleRequest('/news/search?query=First%20body')->getContent();
        self::assertStringContainsString('/news/a/same.html', $search);
        $this->handleRequest('/news/a/same.html');
        self::assertSame(1, $this->api->getItem($first)->viewCount);
        Capsule::table('collection_sections')->where('id', $a)->update(['active' => false]);
        self::assertSame(404, $this->handleRequest('/news/a/same.html')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/news/a/')->getStatusCode());
    }

    public function testCommentAndVoteHttpContractsUseCollectionIdsAndCurrentActor(): void
    {
        $id = $this->item('Public body');
        $user = FunctionalUserFactory::create();
        $cookies = $this->actingAs($user);
        $response = $this->json('/news/comments/add/' . $id, ['comment' => '<b>Comment</b>', 'user_id' => 999, 'user_data' => ['ip' => 'fake']], $cookies);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame($user->id, (int) Capsule::table('news_comments')->where('item_id', $id)->value('user_id'));
        $list = json_decode((string) $this->handleRequest('/news/comments/' . $id)->getContent(), true);
        self::assertSame(1, $list['total']);
        self::assertArrayNotHasKey('ip', $list['data'][0]);
        self::assertStringContainsString('Comment', $list['data'][0]['text']);
        $vote = $this->json('/news/add_vote/' . $id . '/1', [], $cookies);
        self::assertSame(200, $vote->getStatusCode());
        self::assertSame(1, json_decode((string) $vote->getContent(), true)['rating']);
        self::assertSame(404, $this->handleRequest('/news/add_vote/' . $id . '/1')->getStatusCode());
        self::assertSame(1, Capsule::table('news_votes')->count());
        self::assertSame(200, $this->json('/news/comments/del', ['comment_id' => $list['data'][0]['id']], $cookies)->getStatusCode());
        self::assertSame(0, Capsule::table('news_comments')->whereNull('deleted_at')->count());
        Capsule::table('collection_items')->where('id', $id)->update(['active' => false]);
        self::assertSame(404, $this->handleRequest('/news/comments/' . $id)->getStatusCode());
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
        $this->container()->get(\Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer::class)->rebuildCollection($this->collection);
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
            } catch (\Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException) {
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

    /** @param array<string, mixed> $body
     * @param array<string, string> $cookies
     */
    private function json(string $url, array $body, array $cookies): \Symfony\Component\HttpFoundation\Response
    {
        $request = Request::create($url, 'POST', [], $cookies, [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $this->container()->get(Csrf::class)->getToken()], json_encode($body, JSON_THROW_ON_ERROR));
        return $this->container()->get(Kernel::class)->handle($request);
    }
}
