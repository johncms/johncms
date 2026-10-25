<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The news in the panel as they are before the list is rebuilt: the page of the module, the
 * sections and the articles of one level of the tree, and deleting an article or a section.
 *
 * Recorded as the code behaves, odd answers included — each is marked where it is asserted.
 */
final class AdminContentTest extends FunctionalTestCase
{
    public function testThePageOfTheModuleLeadsToTheContentAndToTheSettings(): void
    {
        $content = $this->page('/admin/news');

        self::assertStringContainsString('href="/admin/news/content/"', $content);
        self::assertStringContainsString('href="/admin/news/settings/"', $content);
    }

    public function testTheRootListsTheSectionsAndTheArticlesOfTheRootOnly(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $rootArticle = $this->article('Root article', 'root-article', 0);
        $inSection = $this->article('In a section', 'in-a-section', $releases);
        // The form of the panel stores 0 for the root, the root of the list asks for 0: an article
        // whose section is null is listed nowhere in the panel.
        $withoutSection = $this->article('Without a section', 'without-a-section', null);

        $content = $this->page('/admin/news/content');

        self::assertStringContainsString('href="/admin/news/content/' . $releases . '/"', $content);
        self::assertStringContainsString('href="/admin/news/edit_article/' . $rootArticle . '/"', $content);
        self::assertStringNotContainsString('href="/admin/news/content/' . $nested . '/"', $content);
        self::assertStringNotContainsString('href="/admin/news/edit_article/' . $inSection . '/"', $content);
        self::assertStringNotContainsString('href="/admin/news/edit_article/' . $withoutSection . '/"', $content);
    }

    public function testASectionListsWhatIsDirectlyInIt(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $deeper = $this->section('Deeper', 'deeper', $nested);
        $inSection = $this->article('In a section', 'in-a-section', $releases);
        $inNested = $this->article('In the nested one', 'in-the-nested-one', $nested);

        $content = $this->page('/admin/news/content/' . $releases);

        self::assertStringContainsString('href="/admin/news/content/' . $nested . '/"', $content);
        self::assertStringContainsString('href="/admin/news/edit_article/' . $inSection . '/"', $content);
        self::assertStringNotContainsString('href="/admin/news/content/' . $deeper . '/"', $content);
        self::assertStringNotContainsString('href="/admin/news/edit_article/' . $inNested . '/"', $content);
    }

    public function testAnEmptyLevelSaysSo(): void
    {
        // The language of the page depends on what ran before: the row that says it is looked for.
        self::assertStringContainsString('<td colspan="7" class="text-center fw-bold">', $this->page('/admin/news/content'));
    }

    public function testAnUnknownSectionIsNotFound(): void
    {
        self::assertSame(Response::HTTP_NOT_FOUND, $this->request('/admin/news/content/999')->getStatusCode());
    }

    public function testTheConfirmationOfDeletingAnArticleIsAFragmentThatPostsWithAToken(): void
    {
        $article = $this->article('Old news', 'old-news', 0);

        $content = $this->page('/admin/news/del_article/' . $article);

        self::assertStringStartsWith('<form action="/admin/news/del_article/' . $article . '" method="post">', trim($content));
        self::assertMatchesRegularExpression('~name="delete_token" value="[^"]+"~', $content);
        self::assertStringContainsString('<b>Old news</b>', $content);
    }

    public function testAnArticleIsDeletedWithTheTokenOfItsConfirmation(): void
    {
        $releases = $this->section('Releases', 'releases');
        $article = $this->article('Old news', 'old-news', $releases);
        $supervisor = FunctionalUserFactory::createSupervisor();
        $cookies = $this->actingAs($supervisor);

        $token = $this->deleteToken('/admin/news/del_article/' . $article, $cookies);
        $response = $this->handleRequest(
            '/admin/news/del_article/' . $article,
            'POST',
            $this->withCsrfToken(['delete_token' => $token]),
            cookies: $cookies
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/content/' . $releases, $response->headers->get('Location'));
        self::assertNull(NewsArticle::query()->find($article));
        // The row stays, marked as deleted.
        self::assertTrue(Capsule::table('news_articles')->where('id', $article)->whereNotNull('deleted_at')->exists());
    }

    public function testAPostWithoutTheTokenOfTheConfirmationDeletesNothing(): void
    {
        $article = $this->article('Old news', 'old-news', 0);

        $response = $this->postForm('/admin/news/del_article/' . $article, ['delete_token' => 'forged'], FunctionalUserFactory::createSupervisor());

        // The confirmation is shown again.
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull(NewsArticle::query()->find($article));
    }

    public function testDeletingAnUnknownArticleIsAServerError(): void
    {
        // Odd: a missing article answers 500 rather than 404.
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->request('/admin/news/del_article/999')->getStatusCode());
    }

    public function testDeletingASectionTakesItsSubsectionsAndTheirArticlesWithIt(): void
    {
        $parent = $this->section('Parent', 'parent');
        $releases = $this->section('Releases', 'releases', $parent);
        $nested = $this->section('Nested', 'nested', $releases);
        $sibling = $this->section('Sibling', 'sibling', $parent);
        $inSection = $this->article('In a section', 'in-a-section', $releases);
        $inNested = $this->article('In the nested one', 'in-the-nested-one', $nested);
        $inSibling = $this->article('In the sibling', 'in-the-sibling', $sibling);
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        $token = $this->deleteToken('/admin/news/del_section/' . $releases, $cookies);
        $response = $this->handleRequest(
            '/admin/news/del_section/' . $releases,
            'POST',
            $this->withCsrfToken(['delete_token' => $token]),
            cookies: $cookies
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/content/' . $parent, $response->headers->get('Location'));
        self::assertNull(NewsSection::query()->find($releases));
        self::assertNull(NewsSection::query()->find($nested));
        self::assertNull(NewsArticle::query()->find($inSection));
        self::assertNull(NewsArticle::query()->find($inNested));
        self::assertNotNull(NewsSection::query()->find($sibling));
        self::assertNotNull(NewsArticle::query()->find($inSibling));
    }

    public function testDeletingAnUnknownSectionIsAServerError(): void
    {
        // Odd: a missing section answers 500 rather than 404.
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->request('/admin/news/del_section/999')->getStatusCode());
    }

    public function testAMemberOfThePanelWithoutThePermissionIsNotLetIn(): void
    {
        $member = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 60);

        $response = $this->handleRequest('/admin/news/content', cookies: $this->actingAs($member));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    private function page(string $uri): string
    {
        $response = $this->request($uri);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);

        return (string) $response->getContent();
    }

    private function request(string $uri): Response
    {
        return $this->handleRequest($uri, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
    }

    /**
     * @param array<string, string> $cookies
     */
    private function deleteToken(string $uri, array $cookies): string
    {
        $content = (string) $this->handleRequest($uri, cookies: $cookies)->getContent();
        self::assertSame(1, preg_match('~name="delete_token" value="([^"]+)"~', $content, $match));

        return $match[1];
    }

    private function section(string $name, string $code, ?int $parent = 0): int
    {
        return Fixture::insert('news_sections', [
            'name'       => $name,
            'code'       => $code,
            'parent'     => $parent,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function article(string $name, string $code, ?int $section): int
    {
        return Fixture::insert('news_articles', [
            'name'       => $name,
            'code'       => $code,
            'section_id' => $section,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
