<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Johncms\Modules\News\Domain\Models\NewsSection;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The form of a section in the panel as it is before it is rebuilt: what adding and changing a
 * section writes, where it returns to, and what it refuses.
 *
 * Recorded as the code behaves, odd answers included — each is marked where it is asserted.
 */
final class AdminSectionFormTest extends FunctionalTestCase
{
    public function testASectionIsAddedUnderTheSectionOfTheAddress(): void
    {
        $releases = $this->section('Releases', 'releases');

        $response = $this->postForm('/admin/news/add_section/' . $releases, [
            'name'        => 'Version 10',
            'code'        => '',
            'keywords'    => 'ten',
            'description' => 'The tenth',
            'text'        => 'After the list',
        ], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/content/' . $releases, $response->headers->get('Location'));

        $section = NewsSection::query()->where('parent', $releases)->firstOrFail();
        self::assertSame('Version 10', $section->name);
        self::assertSame('version-10', $section->code);
        self::assertSame('ten', $section->keywords);
        self::assertSame('The tenth', $section->description);
        self::assertSame('After the list', $section->text);
    }

    public function testASectionWithoutANameIsRefused(): void
    {
        $response = $this->postForm('/admin/news/add_section/0', ['name' => ' ', 'code' => 'nameless'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(NewsSection::query()->where('code', 'nameless')->exists());
    }

    public function testACodeTakenUnderTheSameSectionIsRefused(): void
    {
        $releases = $this->section('Releases', 'releases');
        $this->section('Taken', 'taken', $releases);

        $response = $this->postForm('/admin/news/add_section/' . $releases, ['name' => 'Another', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, NewsSection::query()->where('code', 'taken')->count());
    }

    public function testACodeTakenAtTheRootIsRefused(): void
    {
        $this->section('Taken', 'taken');

        $response = $this->postForm('/admin/news/add_section/0', ['name' => 'Another', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(1, NewsSection::query()->where('code', 'taken')->count());
    }

    public function testAnUnknownSectionToAddUnderIsNotFound(): void
    {
        $response = $this->handleRequest('/admin/news/add_section/999', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testTheFormOfASectionShowsWhatItHolds(): void
    {
        $section = $this->section('Releases', 'releases');

        $content = $this->page('/admin/news/edit_section/' . $section);

        self::assertStringContainsString('value="Releases"', $content);
        self::assertStringContainsString('value="releases"', $content);
    }

    public function testASectionIsChangedWhereItStands(): void
    {
        $parent = $this->section('Parent', 'parent');
        $section = $this->section('Releases', 'releases', $parent);

        $response = $this->postForm('/admin/news/edit_section/' . $section, ['name' => 'Versions', 'code' => 'Versions'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/news/content/' . $parent, $response->headers->get('Location'));
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

        $this->postForm('/admin/news/edit_section/' . $section, ['name' => 'Releases', 'code' => 'versions'], $supervisor);

        self::assertStringContainsString('href="/news/versions/ten.html"', (string) $this->handleRequest('/admin/news', cookies: $cookies)->getContent());
    }

    public function testAChangeToACodeTakenBesideItIsRefused(): void
    {
        $this->section('Taken', 'taken');
        $section = $this->section('Releases', 'releases');

        $response = $this->postForm('/admin/news/edit_section/' . $section, ['name' => 'Releases', 'code' => 'taken'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('releases', NewsSection::query()->findOrFail($section)->code);
    }

    public function testAnUnknownSectionIsNotFound(): void
    {
        $response = $this->handleRequest('/admin/news/edit_section/999', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
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
