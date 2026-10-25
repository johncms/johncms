<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The list of the news in the panel: every article or every section flat, narrowed by the section
 * with its subtree, the status, the author and a search, ordered from the heads of the columns.
 */
final class AdminNewsListTest extends FunctionalTestCase
{
    public function testTheArticlesOfEverySectionAreListedTogether(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $atTheRoot = $this->article('At the root', 0);
        $withoutSection = $this->article('Without a section', null);
        $inSection = $this->article('In a section', $releases);
        $inNested = $this->article('In the nested one', $nested);

        $content = $this->page('/admin/news');

        foreach ([$atTheRoot, $withoutSection, $inSection, $inNested] as $article) {
            self::assertStringContainsString($this->editLink($article), $content);
        }
        // The path of the section stands beside the article.
        self::assertStringContainsString('Releases › Nested', $content);
    }

    public function testASectionNarrowsTheArticlesToItsSubtree(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $other = $this->section('Other', 'other');
        $inSection = $this->article('In a section', $releases);
        $inNested = $this->article('In the nested one', $nested);
        $inOther = $this->article('In another one', $other);
        $atTheRoot = $this->article('At the root', 0);

        $content = $this->page('/admin/news?section=' . $releases);

        self::assertStringContainsString($this->editLink($inSection), $content);
        self::assertStringContainsString($this->editLink($inNested), $content);
        self::assertStringNotContainsString($this->editLink($inOther), $content);
        self::assertStringNotContainsString($this->editLink($atTheRoot), $content);
    }

    public function testTheRootHoldsTheArticlesOutsideEverySection(): void
    {
        $releases = $this->section('Releases', 'releases');
        $atTheRoot = $this->article('At the root', 0);
        $withoutSection = $this->article('Without a section', null);
        $inSection = $this->article('In a section', $releases);

        $content = $this->page('/admin/news?section=0');

        self::assertStringContainsString($this->editLink($atTheRoot), $content);
        self::assertStringContainsString($this->editLink($withoutSection), $content);
        self::assertStringNotContainsString($this->editLink($inSection), $content);
    }

    public function testTheStatusIsWorkedOutFromTheSwitchAndTheDates(): void
    {
        $published = $this->article('Published', 0);
        $draft = $this->article('Draft', 0, ['active' => 0]);
        $scheduled = $this->article('Scheduled', 0, ['active_from' => date('Y-m-d H:i:s', time() + 86400)]);
        $expired = $this->article('Expired', 0, ['active_to' => date('Y-m-d H:i:s', time() - 86400)]);
        $all = [$published, $draft, $scheduled, $expired];

        foreach (['published' => $published, 'draft' => $draft, 'scheduled' => $scheduled, 'expired' => $expired] as $status => $expected) {
            $content = $this->page('/admin/news?status=' . $status);

            foreach ($all as $article) {
                $article === $expected
                    ? self::assertStringContainsString($this->editLink($article), $content, $status)
                    : self::assertStringNotContainsString($this->editLink($article), $content, $status);
            }
        }
    }

    public function testTheAuthorNarrowsTheArticlesToWhatTheyWrote(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Bilbo']);
        $byAuthor = $this->article('By Bilbo', 0, ['created_by' => $author->id]);
        $byNobody = $this->article('By nobody', 0);

        $content = $this->page('/admin/news?author=' . $author->id);

        self::assertStringContainsString($this->editLink($byAuthor), $content);
        self::assertStringNotContainsString($this->editLink($byNobody), $content);
        self::assertStringContainsString('href="/admin/users/' . $author->id . '">Bilbo</a>', $content);
    }

    public function testTheSearchLooksAtTheNameTheCodeAndTheNumber(): void
    {
        $byName = $this->article('Release notes', 0, ['code' => 'first']);
        $byCode = $this->article('Second', 0, ['code' => 'release-of-the-year']);
        $other = $this->article('Something else', 0, ['code' => 'third']);

        $content = $this->page('/admin/news?q=release');

        self::assertStringContainsString($this->editLink($byName), $content);
        self::assertStringContainsString($this->editLink($byCode), $content);
        self::assertStringNotContainsString($this->editLink($other), $content);
        self::assertStringContainsString($this->editLink($other), $this->page('/admin/news?q=' . $other));
    }

    public function testTheArticlesAreOrderedByTheColumnAsked(): void
    {
        $this->article('Bravo', 0);
        $this->article('Alpha', 0);
        $this->article('Charlie', 0);

        $byName = $this->page('/admin/news?sort=name');
        $newestFirst = $this->page('/admin/news');

        self::assertLessThan(strpos($byName, '>Bravo<'), strpos($byName, '>Alpha<'));
        self::assertLessThan(strpos($byName, '>Charlie<'), strpos($byName, '>Bravo<'));
        self::assertLessThan(strpos($newestFirst, '>Alpha<'), strpos($newestFirst, '>Charlie<'));
    }

    public function testTheSectionsAreListedWithTheSectionsAboveThem(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $this->article('In a section', $releases);
        $this->article('Another one', $releases);

        $content = $this->page('/admin/news?tab=sections');

        self::assertStringContainsString('href="/admin/news/edit_section/' . $releases . '"', $content);
        self::assertStringContainsString('href="/admin/news/edit_section/' . $nested . '"', $content);
        // The number of the articles leads to them.
        self::assertStringContainsString('href="/admin/news?section=' . $releases . '">2</a>', $content);
    }

    public function testASectionNarrowsTheSectionsToThoseUnderIt(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $other = $this->section('Other', 'other');

        $content = $this->page('/admin/news?tab=sections&section=' . $releases);

        self::assertStringContainsString('href="/admin/news/edit_section/' . $nested . '"', $content);
        self::assertStringNotContainsString('href="/admin/news/edit_section/' . $releases . '"', $content);
        self::assertStringNotContainsString('href="/admin/news/edit_section/' . $other . '"', $content);
    }

    public function testTheLiveListGetsItsRegionsAlone(): void
    {
        $this->article('Live', 0);

        $response = $this->handleRequest(
            '/admin/news?q=live',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertStringStartsWith('<div class="admin-list-tabs" data-live-list-region="tabs">', trim((string) $response->getContent()));
        self::assertStringContainsString('data-live-list-region="results"', (string) $response->getContent());
    }

    public function testTheAddressOfALevelOfTheTreeLeadsToTheListOfThatSection(): void
    {
        $releases = $this->section('Releases', 'releases');

        self::assertSame('/admin/news?section=' . $releases, $this->request('/admin/news/content/' . $releases)->headers->get('Location'));
        self::assertSame('/admin/news', $this->request('/admin/news/content')->headers->get('Location'));
    }

    public function testAMemberOfThePanelWithoutThePermissionIsNotLetIn(): void
    {
        $member = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 60);

        $response = $this->handleRequest('/admin/news', cookies: $this->actingAs($member));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    private function editLink(int $article): string
    {
        return 'href="/admin/news/edit_article/' . $article . '/"';
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

    /**
     * @param array<string, mixed> $attributes
     */
    private function article(string $name, ?int $section, array $attributes = []): int
    {
        return Fixture::insert('news_articles', $attributes + [
            'name'       => $name,
            'code'       => strtolower(str_replace(' ', '-', $name)),
            'section_id' => $section,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
