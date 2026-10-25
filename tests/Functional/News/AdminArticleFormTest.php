<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The form of an article in the panel: what adding and changing an article writes, where saving
 * leads, and what it refuses.
 */
final class AdminArticleFormTest extends FunctionalTestCase
{
    public function testAnArticleIsAddedToTheSectionOfTheAddress(): void
    {
        $releases = $this->section('releases');
        $supervisor = FunctionalUserFactory::createSupervisor();

        $response = $this->postForm('/admin/news/articles/new', [
            'section_id'   => (string) $releases,
            'active'       => '1',
            'name'         => 'JohnCMS 10',
            'page_title'   => 'The tenth one',
            'active_from'  => '01.11.2026 10:30',
            'active_to'    => '',
            'code'         => '',
            'keywords'     => 'cms, release',
            'description'  => 'What is new',
            'tags'         => 'release, cms',
            'preview_text' => '<p>Lead</p>',
            'text'         => '<p>Text</p>',
        ], $supervisor);

        $article = NewsArticle::query()->where('section_id', $releases)->firstOrFail();
        // Saving stays on the form of the article.
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/articles/' . $article->id . '/edit', $response->headers->get('Location'));

        self::assertSame('JohnCMS 10', $article->name);
        // The code is made of the name when none is given.
        self::assertSame('johncms-10', $article->code);
        self::assertTrue($article->active);
        self::assertSame('2026-11-01 10:30:00', $article->getRawOriginal('active_from'));
        self::assertNull($article->getRawOriginal('active_to'));
        self::assertSame('The tenth one', $article->page_title);
        self::assertSame('cms, release', $article->keywords);
        self::assertSame('What is new', $article->description);
        self::assertSame(['release', 'cms'], $article->tags);
        self::assertSame('<p>Lead</p>', $article->getRawOriginal('preview_text'));
        self::assertSame('<p>Text</p>', $article->getRawOriginal('text'));
        self::assertSame($supervisor->id, (int) $article->created_by);
        // The search reads a text of its own, the tags stripped.
        self::assertSame('JohnCMS 10 Lead Text', Capsule::table('news_search_index')->where('article_id', $article->id)->value('text'));
    }

    public function testAGivenCodeIsMadeIntoASlug(): void
    {
        $this->postForm('/admin/news/articles/new', ['name' => 'Anything', 'code' => 'Hello World!'], FunctionalUserFactory::createSupervisor());

        self::assertTrue(NewsArticle::query()->where('code', 'hello-world')->exists());
    }

    public function testAnArticleWithoutANameIsRefused(): void
    {
        $response = $this->postForm('/admin/news/articles/new', ['name' => '  ', 'code' => 'nameless'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(NewsArticle::query()->where('code', 'nameless')->exists());
        self::assertStringContainsString('id="article-name" name="name"', (string) $response->getContent());
        self::assertMatchesRegularExpression('~class="form-control form-control-lg is-invalid" id="article-name"~', (string) $response->getContent());
    }

    public function testACodeTakenInTheSameSectionIsRefused(): void
    {
        $releases = $this->section('releases');
        $this->article('Taken', 'taken', $releases);

        $response = $this->postForm('/admin/news/articles/new', ['section_id' => (string) $releases, 'name' => 'Another', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, NewsArticle::query()->where('code', 'taken')->count());
    }

    public function testTheSameCodeInAnotherSectionIsAllowed(): void
    {
        $this->article('Taken', 'taken', $this->section('releases'));

        $this->postForm('/admin/news/articles/new', ['section_id' => (string) $this->section('guides'), 'name' => 'Another', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(2, NewsArticle::query()->where('code', 'taken')->count());
    }

    public function testACodeTakenAtTheRootIsRefused(): void
    {
        $this->article('Taken', 'taken', 0);
        $this->article('Stored without a section', 'taken-too', 0, ['section_id' => null]);
        $supervisor = FunctionalUserFactory::createSupervisor();

        $first = $this->postForm('/admin/news/articles/new', ['name' => 'Another', 'code' => 'taken'], $supervisor);
        $second = $this->postForm('/admin/news/articles/new', ['name' => 'Another', 'code' => 'taken-too'], $supervisor);

        self::assertSame(Response::HTTP_OK, $first->getStatusCode());
        self::assertSame(Response::HTTP_OK, $second->getStatusCode());
        self::assertSame(1, NewsArticle::query()->where('code', 'taken')->count());
        self::assertSame(1, NewsArticle::query()->where('code', 'taken-too')->count());
    }

    public function testADateThatIsNotOneIsRefused(): void
    {
        $response = $this->postForm('/admin/news/articles/new', ['name' => 'Dated', 'active_from' => '31.02.2026 10:00'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(NewsArticle::query()->where('code', 'dated')->exists());
    }

    public function testASectionThatDoesNotExistIsRefused(): void
    {
        $response = $this->postForm('/admin/news/articles/new', ['section_id' => '999', 'name' => 'Lost'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(NewsArticle::query()->where('code', 'lost')->exists());
    }

    public function testANewArticleStandsInTheSectionTheListWasNarrowedTo(): void
    {
        $releases = $this->section('releases');

        $content = $this->page('/admin/news/articles/new?section=' . $releases);

        self::assertStringContainsString('<option value="' . $releases . '" selected>', $content);
        // The pictures of the text are uploaded where the panel takes them.
        self::assertStringContainsString('upload_url="/admin/news/upload_file"', $content);
    }

    public function testSaveAndCloseReturnsToTheListOfTheSection(): void
    {
        $releases = $this->section('releases');

        $response = $this->postForm('/admin/news/articles/new', ['section_id' => (string) $releases, 'name' => 'Closed', 'close' => '1'], FunctionalUserFactory::createSupervisor());

        self::assertSame('/admin/news?section=' . $releases, $response->headers->get('Location'));
    }

    public function testTheFormOfAnArticleShowsWhatItHolds(): void
    {
        $article = $this->article('Old news', 'old-news', 0, [
            'active_from' => '2026-11-01 10:30:00',
            'tags'        => 'one,two',
        ]);

        $content = $this->page('/admin/news/articles/' . $article . '/edit');

        self::assertStringContainsString('value="Old news"', $content);
        self::assertStringContainsString('value="old-news"', $content);
        self::assertStringContainsString('value="01.11.2026 10:30"', $content);
        self::assertStringContainsString('value="one, two"', $content);
    }

    public function testAnArticleIsChanged(): void
    {
        $releases = $this->section('releases');
        $article = $this->article('Old news', 'old-news', $releases, ['active_to' => '2026-12-01 00:00:00']);
        Fixture::insert('news_search_index', ['article_id' => $article, 'text' => 'stale']);
        $supervisor = FunctionalUserFactory::createSupervisor();

        $response = $this->postForm('/admin/news/articles/' . $article . '/edit', [
            'section_id'  => (string) $releases,
            'active'      => '0',
            'name'        => 'New news',
            'code'        => 'new-news',
            'active_from' => '',
            'active_to'   => '',
            'text'        => '<p>Fresh</p>',
        ], $supervisor);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/articles/' . $article . '/edit', $response->headers->get('Location'));

        $changed = NewsArticle::query()->findOrFail($article);
        self::assertSame('New news', $changed->name);
        self::assertSame('new-news', $changed->code);
        self::assertFalse($changed->active);
        self::assertNull($changed->getRawOriginal('active_to'));
        self::assertSame($supervisor->id, (int) $changed->updated_by);
        // The parts are joined with a space each, so the empty lead leaves two of them.
        self::assertSame('New news  Fresh', Capsule::table('news_search_index')->where('article_id', $article)->value('text'));
    }

    public function testAChangeToACodeTakenInTheSectionIsRefused(): void
    {
        $releases = $this->section('releases');
        $this->article('Taken', 'taken', $releases);
        $article = $this->article('Old news', 'old-news', $releases);

        $response = $this->postForm('/admin/news/articles/' . $article . '/edit', ['section_id' => (string) $releases, 'name' => 'Old news', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('old-news', NewsArticle::query()->findOrFail($article)->code);
    }

    public function testAnArticleMovesToAnotherSectionWhereItsCodeIsFree(): void
    {
        $releases = $this->section('releases');
        $guides = $this->section('guides');
        $this->article('Taken', 'taken', $guides);
        $article = $this->article('Old news', 'old-news', $releases);
        $supervisor = FunctionalUserFactory::createSupervisor();

        $this->postForm('/admin/news/articles/' . $article . '/edit', ['section_id' => (string) $guides, 'name' => 'Old news', 'code' => 'taken'], $supervisor);
        self::assertSame($releases, NewsArticle::query()->findOrFail($article)->section_id);

        $this->postForm('/admin/news/articles/' . $article . '/edit', ['section_id' => (string) $guides, 'name' => 'Old news', 'code' => 'old-news'], $supervisor);
        self::assertSame($guides, NewsArticle::query()->findOrFail($article)->section_id);
    }

    public function testAnUnknownArticleIsNotFound(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/news/articles/999/edit', cookies: $this->actingAs($supervisor))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $this->postForm('/admin/news/articles/999/edit', ['name' => 'x'], $supervisor)->getStatusCode());
    }

    private function page(string $uri): string
    {
        $response = $this->handleRequest($uri, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);

        return (string) $response->getContent();
    }

    private function section(string $code): int
    {
        return Fixture::insert('news_sections', ['name' => ucfirst($code), 'code' => $code, 'parent' => 0, 'created_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function article(string $name, string $code, int $section, array $attributes = []): int
    {
        return Fixture::insert('news_articles', $attributes + [
            'name'       => $name,
            'code'       => $code,
            'section_id' => $section,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
