<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * What the forms of a section of the forum write: where a new section goes, what moving one takes
 * along, and the one place a section cannot go.
 */
final class ForumAdminSectionFormTest extends FunctionalTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
    }

    public function testANewSectionGoesToTheEndOfItsParent(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $this->section('An older section', $category, 1, 5);

        $response = $this->postForm('/admin/forum/sections/new', $this->fields([
            'name'         => 'Fresh section',
            'description'  => 'What it is about',
            'parent'       => $category,
            'access'       => 4,
            'section_type' => 1,
        ]), $this->admin);

        $section = ForumSection::query()->where('name', 'Fresh section')->firstOrFail();
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/forum/sections/' . $section->id . '/edit', $response->headers->get('Location'), 'Saving stays on the form.');
        self::assertSame($category, (int) $section->parent);
        self::assertSame(6, $section->sort);
        self::assertSame('fresh-section', $section->slug);
        self::assertSame('What it is about', $section->description);
        self::assertSame(4, $section->access);
        self::assertSame(1, $section->section_type);
    }

    public function testASectionKeptUnderItsParentSavesWhatWasTyped(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $section = $this->section('Old name', $category, 1, 3);

        $this->postForm('/admin/forum/sections/' . $section . '/edit', $this->fields([
            'name'             => 'New name',
            'sort'             => 42,
            'parent'           => $category,
            'meta_keywords'    => 'some, words',
            'meta_description' => 'For the search engines',
        ]), $this->admin);

        $saved = ForumSection::query()->findOrFail($section);
        self::assertSame('New name', $saved->name);
        self::assertSame('new-name', $saved->slug, 'The address follows the name.');
        self::assertSame(42, $saved->sort);
        self::assertSame('some, words', $saved->meta_keywords);
        self::assertSame('For the search engines', $saved->meta_description);
    }

    public function testAMovedSectionGoesToTheEndOfItsNewParentWithItsFiles(): void
    {
        $from = $this->section('From', 0, 0, 1);
        $to = $this->section('To', 0, 0, 2);
        $this->section('Already there', $to, 1, 7);
        $section = $this->section('Moving', $from, 1, 1);
        $file = Fixture::insert('cms_forum_files', ['cat' => $from, 'subcat' => $section, 'filename' => 'a.txt']);

        $this->postForm('/admin/forum/sections/' . $section . '/edit', $this->fields([
            'name'   => 'Moving',
            'sort'   => 1,
            'parent' => $to,
        ]), $this->admin);

        $saved = ForumSection::query()->findOrFail($section);
        self::assertSame($to, (int) $saved->parent);
        self::assertSame(8, $saved->sort, 'The order typed into the form gives way to the end of the new parent.');
        self::assertSame($to, (int) ForumFile::query()->findOrFail($file)->cat);
    }

    public function testASectionCannotGoUnderASectionOfItsOwn(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $child = $this->section('Its child', $category, 0, 1);

        $response = $this->postForm('/admin/forum/sections/' . $category . '/edit', $this->fields([
            'name'         => 'A category',
            'sort'         => 1,
            'parent'       => $child,
            'section_type' => 0,
        ]), $this->admin);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), 'The form comes back.');
        self::assertSame(0, (int) ForumSection::query()->findOrFail($category)->parent);
    }

    public function testSaveAndCloseLeadsBackToTheTree(): void
    {
        $response = $this->postForm('/admin/forum/sections/new', $this->fields(['name' => 'A category', 'parent' => 0, 'section_type' => 0, 'close' => 1]), $this->admin);

        self::assertSame('/admin/forum', $response->headers->get('Location'));
    }

    public function testAnEmptyOrderPutsAnEditedSectionAtTheEnd(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $this->section('The last one', $category, 1, 9);
        $section = $this->section('Edited', $category, 1, 2);

        $this->postForm('/admin/forum/sections/' . $section . '/edit', $this->fields(['name' => 'Edited', 'parent' => $category]), $this->admin);

        self::assertSame(10, ForumSection::query()->findOrFail($section)->sort);
    }

    public function testASectionCannotGoUnderASectionOfTopics(): void
    {
        $topics = $this->section('Holds topics', 0, 1, 1);

        $response = $this->postForm('/admin/forum/sections/new', $this->fields(['name' => 'Nowhere to go', 'parent' => $topics]), $this->admin);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse(ForumSection::query()->where('name', 'Nowhere to go')->exists());
    }

    public function testASectionWithTopicsKeepsHoldingTopics(): void
    {
        $section = $this->section('With topics', 0, 1, 1);
        Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A hidden topic', 'slug' => 'a-hidden-topic', 'deleted' => 1]);

        $response = $this->postForm('/admin/forum/sections/' . $section . '/edit', $this->fields(['name' => 'With topics', 'parent' => 0, 'section_type' => 0]), $this->admin);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('переместите их', (string) $response->getContent());
        self::assertSame(1, ForumSection::query()->findOrFail($section)->section_type, 'A hidden topic counts too.');
    }

    public function testASectionWithSectionsKeepsHoldingSections(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $this->section('Its child', $category, 1, 1);

        $this->postForm('/admin/forum/sections/' . $category . '/edit', $this->fields(['name' => 'A category', 'parent' => 0, 'section_type' => 1]), $this->admin);

        self::assertSame(0, ForumSection::query()->findOrFail($category)->section_type);
    }

    public function testTheFormOfASectionShowsWhatItHolds(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $section = $this->section('A section', $category, 1, 1);
        Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic']);

        $response = $this->handleRequest('/admin/forum/sections/' . $section . '/edit', cookies: $this->actingAs($this->admin));

        $body = (string) $response->getContent();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('value="A section"', $body);
        self::assertStringContainsString('<option value="' . $category . '" selected>A category</option>', $body, 'The parent is chosen.');
        self::assertStringNotContainsString('>A section</option>', $body, 'A section is not offered as its own parent.');
    }

    public function testAMissingSectionHasNoForm(): void
    {
        $response = $this->handleRequest('/admin/forum/sections/999999/edit', cookies: $this->actingAs($this->admin));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    private function section(string $name, int $parent, int $type, int $sort): int
    {
        return Fixture::insert('forum_sections', [
            'name'         => $name,
            'slug'         => strtolower(str_replace(' ', '-', $name)),
            'parent'       => $parent,
            'section_type' => $type,
            'sort'         => $sort,
            'access'       => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function fields(array $fields): array
    {
        return $fields + [
            'description'      => '',
            'sort'             => '',
            'meta_description' => '',
            'meta_keywords'    => '',
            'access'           => 0,
            'section_type'     => 1,
        ];
    }
}
