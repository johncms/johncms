<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Johncms\Modules\News\Domain\Models\NewsSection;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The form of a section in the panel: what adding and changing a section writes, where saving
 * leads, and what it refuses.
 */
final class AdminSectionFormTest extends FunctionalTestCase
{
    public function testASectionIsAddedUnderTheSectionOfTheAddress(): void
    {
        $releases = $this->section('Releases', 'releases');

        $response = $this->postForm('/admin/news/sections/new', [
            'parent'      => (string) $releases,
            'name'        => 'Version 10',
            'code'        => '',
            'keywords'    => 'ten',
            'description' => 'The tenth',
            'text'        => 'After the list',
        ], FunctionalUserFactory::createSupervisor());

        $section = NewsSection::query()->where('parent', $releases)->firstOrFail();
        // Saving stays on the form of the section.
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/sections/' . $section->id . '/edit', $response->headers->get('Location'));

        self::assertSame('Version 10', $section->name);
        self::assertSame('version-10', $section->code);
        self::assertSame('ten', $section->keywords);
        self::assertSame('The tenth', $section->description);
        self::assertSame('After the list', $section->text);
    }

    public function testASectionWithoutANameIsRefused(): void
    {
        $response = $this->postForm('/admin/news/sections/new', ['name' => ' ', 'code' => 'nameless'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(NewsSection::query()->where('code', 'nameless')->exists());
    }

    public function testACodeTakenUnderTheSameSectionIsRefused(): void
    {
        $releases = $this->section('Releases', 'releases');
        $this->section('Taken', 'taken', $releases);

        $response = $this->postForm('/admin/news/sections/new', ['parent' => (string) $releases, 'name' => 'Another', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, NewsSection::query()->where('code', 'taken')->count());
    }

    public function testACodeTakenAtTheRootIsRefused(): void
    {
        $this->section('Taken', 'taken');

        $response = $this->postForm('/admin/news/sections/new', ['name' => 'Another', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, NewsSection::query()->where('code', 'taken')->count());
    }

    public function testAParentThatDoesNotExistIsRefused(): void
    {
        $response = $this->postForm('/admin/news/sections/new', ['parent' => '999', 'name' => 'Lost'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(NewsSection::query()->where('code', 'lost')->exists());
    }

    public function testANewSectionStandsUnderTheSectionTheListWasNarrowedTo(): void
    {
        $releases = $this->section('Releases', 'releases');

        self::assertStringContainsString('<option value="' . $releases . '" selected>', $this->page('/admin/news/sections/new?parent=' . $releases));
    }

    public function testSaveAndCloseReturnsToTheSectionsBesideIt(): void
    {
        $releases = $this->section('Releases', 'releases');

        $response = $this->postForm('/admin/news/sections/new', ['parent' => (string) $releases, 'name' => 'Closed', 'close' => '1'], FunctionalUserFactory::createSupervisor());

        self::assertSame('/admin/news?tab=sections&section=' . $releases, $response->headers->get('Location'));
    }

    public function testTheFormOfASectionShowsWhatItHolds(): void
    {
        $section = $this->section('Releases', 'releases');

        $content = $this->page('/admin/news/sections/' . $section . '/edit');

        self::assertStringContainsString('value="Releases"', $content);
        self::assertStringContainsString('value="releases"', $content);
    }

    public function testASectionIsChangedWhereItStands(): void
    {
        $parent = $this->section('Parent', 'parent');
        $section = $this->section('Releases', 'releases', $parent);

        $response = $this->postForm('/admin/news/sections/' . $section . '/edit', ['parent' => (string) $parent, 'name' => 'Versions', 'code' => 'Versions'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/sections/' . $section . '/edit', $response->headers->get('Location'));
        $changed = NewsSection::query()->findOrFail($section);
        self::assertSame('Versions', $changed->name);
        self::assertSame('versions', $changed->code);
        self::assertSame($parent, $changed->parent);
    }

    public function testANewCodeChangesTheAddressesOfTheArticles(): void
    {
        $section = $this->section('Releases', 'releases');
        Fixture::insert('news_articles', ['name' => 'Ten', 'code' => 'ten', 'section_id' => $section, 'active' => 1, 'created_at' => date('Y-m-d H:i:s')]);
        $supervisor = FunctionalUserFactory::createSupervisor();
        $cookies = $this->actingAs($supervisor);
        // The list asks for the path of the section, which the tree keeps in the cache.
        self::assertStringContainsString('href="/news/releases/ten.html"', (string) $this->handleRequest('/admin/news', cookies: $cookies)->getContent());

        $this->postForm('/admin/news/sections/' . $section . '/edit', ['name' => 'Releases', 'code' => 'versions'], $supervisor);

        self::assertStringContainsString('href="/news/versions/ten.html"', (string) $this->handleRequest('/admin/news', cookies: $cookies)->getContent());
    }

    public function testAChangeToACodeTakenBesideItIsRefused(): void
    {
        $this->section('Taken', 'taken');
        $section = $this->section('Releases', 'releases');

        $response = $this->postForm('/admin/news/sections/' . $section . '/edit', ['name' => 'Releases', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('releases', NewsSection::query()->findOrFail($section)->code);
    }

    public function testASectionMovesUnderAnother(): void
    {
        $releases = $this->section('Releases', 'releases');
        $guides = $this->section('Guides', 'guides');

        $this->postForm('/admin/news/sections/' . $releases . '/edit', ['parent' => (string) $guides, 'name' => 'Releases'], FunctionalUserFactory::createSupervisor());

        self::assertSame($guides, NewsSection::query()->findOrFail($releases)->parent);
    }

    public function testASectionGoesUnderNeitherItselfNorASectionUnderIt(): void
    {
        $releases = $this->section('Releases', 'releases');
        $nested = $this->section('Nested', 'nested', $releases);
        $supervisor = FunctionalUserFactory::createSupervisor();

        foreach ([$releases, $nested] as $parent) {
            $response = $this->postForm('/admin/news/sections/' . $releases . '/edit', ['parent' => (string) $parent, 'name' => 'Releases'], $supervisor);

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertSame(0, NewsSection::query()->findOrFail($releases)->parent);
        }

        // Nor are they offered.
        $content = $this->page('/admin/news/sections/' . $releases . '/edit');
        self::assertStringNotContainsString('<option value="' . $releases . '"', $content);
        self::assertStringNotContainsString('<option value="' . $nested . '"', $content);
    }

    public function testAnUnknownSectionIsNotFound(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/news/sections/999/edit', cookies: $this->actingAs($supervisor))->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $this->postForm('/admin/news/sections/999/edit', ['name' => 'x'], $supervisor)->getStatusCode());
    }

    private function page(string $uri): string
    {
        $response = $this->handleRequest($uri, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);

        return (string) $response->getContent();
    }

    private function section(string $name, string $code, int $parent = 0): int
    {
        return Fixture::insert('news_sections', ['name' => $name, 'code' => $code, 'parent' => $parent, 'created_at' => date('Y-m-d H:i:s')]);
    }
}
