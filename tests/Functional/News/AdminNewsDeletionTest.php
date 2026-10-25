<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Deleting an article or a section from the list of the news: a dialog that says what goes, and
 * the form that returns to the page of the list it was opened from.
 */
final class AdminNewsDeletionTest extends FunctionalTestCase
{
    public function testTheModalGetsTheDialogOfAnArticle(): void
    {
        $article = $this->article('Old news', 0);

        $content = $this->dialog('/admin/news/articles/' . $article . '/delete?back=' . rawurlencode('/admin/news?q=old'));

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/news/articles/' . $article . '/delete">', $content);
        self::assertStringContainsString('name="back" value="/admin/news?q=old"', $content);
        self::assertStringContainsString('Old news', $content);
    }

    public function testAPlainVisitGetsTheDialogAsAPage(): void
    {
        $article = $this->article('Old news', 0);

        $response = $this->handleRequest('/admin/news/articles/' . $article . '/delete', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('admin-dialog-page', (string) $response->getContent());
    }

    public function testAnArticleIsDeletedAndTheFormReturnsToTheList(): void
    {
        $article = $this->article('Old news', 0);

        $response = $this->postForm('/admin/news/articles/' . $article . '/delete', ['back' => '/admin/news?page=2'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news?page=2', $response->headers->get('Location'));
        self::assertNull(NewsArticle::query()->find($article));
        // The row stays, marked as deleted.
        self::assertTrue(Capsule::table('news_articles')->where('id', $article)->whereNotNull('deleted_at')->exists());
    }

    public function testAReturnAddressOutsideTheListIsNotFollowed(): void
    {
        foreach (['https://example.com/admin/news', '//example.com/admin/news', '/admin/newsX', '/admin/users'] as $back) {
            $article = $this->article('Old news ' . $back, 0);

            $response = $this->postForm('/admin/news/articles/' . $article . '/delete', ['back' => $back], FunctionalUserFactory::createSupervisor());

            self::assertSame('/admin/news', $response->headers->get('Location'), $back);
        }
    }

    public function testAnUnknownArticleIsNotFound(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/news/articles/999/delete', cookies: $this->actingAs($supervisor))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $this->postForm('/admin/news/articles/999/delete', [], $supervisor)->getStatusCode());
    }

    public function testTheDialogOfASectionSaysWhatGoesWithIt(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $this->section('Deeper', 'deeper', $nested);
        $this->article('In a section', $releases);
        $this->article('In the nested one', $nested);
        $this->article('One more', $nested);

        $content = $this->dialog('/admin/news/sections/' . $releases . '/delete');

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/news/sections/' . $releases . '/delete">', $content);
        // The language of the page depends on what ran before, so only the numbers are looked for.
        self::assertMatchesRegularExpression('~<li>\D*2\D*</li>\s*<li>\D*3\D*</li>~u', $content);
    }

    public function testASectionIsDeletedWithEverythingUnderIt(): void
    {
        $parent = $this->section('Parent', 'parent');
        $releases = $this->section('Releases', 'releases', $parent);
        $nested = $this->section('Nested', 'nested', $releases);
        $sibling = $this->section('Sibling', 'sibling', $parent);
        $inSection = $this->article('In a section', $releases);
        $inNested = $this->article('In the nested one', $nested);
        $inSibling = $this->article('In the sibling', $sibling);

        $response = $this->postForm('/admin/news/sections/' . $releases . '/delete', ['back' => '/admin/news?tab=sections'], FunctionalUserFactory::createSupervisor());

        self::assertSame('/admin/news?tab=sections', $response->headers->get('Location'));
        self::assertNull(NewsSection::query()->find($releases));
        self::assertNull(NewsSection::query()->find($nested));
        self::assertNull(NewsArticle::query()->find($inSection));
        self::assertNull(NewsArticle::query()->find($inNested));
        self::assertNotNull(NewsSection::query()->find($sibling));
        self::assertNotNull(NewsArticle::query()->find($inSibling));
    }

    public function testAnUnknownSectionIsNotFound(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/news/sections/999/delete', cookies: $this->actingAs($supervisor))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $this->postForm('/admin/news/sections/999/delete', [], $supervisor)->getStatusCode());
    }

    public function testTheListOffersTheDialogsWithTheWayBack(): void
    {
        $releases = $this->section('Releases', 'releases');
        $article = $this->article('Old news', $releases);
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        $articles = (string) $this->handleRequest('/admin/news?q=old', cookies: $cookies)->getContent();
        $sections = (string) $this->handleRequest('/admin/news?tab=sections', cookies: $cookies)->getContent();

        self::assertStringContainsString('href="/admin/news/articles/' . $article . '/delete?back=' . rawurlencode('/admin/news?q=old') . '" data-admin-modal', $articles);
        self::assertStringContainsString('href="/admin/news/sections/' . $releases . '/delete?back=' . rawurlencode('/admin/news?tab=sections') . '" data-admin-modal', $sections);
    }

    private function dialog(string $uri): string
    {
        $response = $this->handleRequest(
            $uri,
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);

        return trim((string) $response->getContent());
    }

    private function section(string $name, string $code, ?int $parent = 0): int
    {
        return Fixture::insert('news_sections', [
            'name'       => $name,
            'code'       => $code,
            'parent'     => $parent,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function article(string $name, ?int $section): int
    {
        return Fixture::insert('news_articles', [
            'name'       => $name,
            'code'       => md5($name),
            'section_id' => $section,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
