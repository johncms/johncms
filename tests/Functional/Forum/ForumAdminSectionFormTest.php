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

        $response = $this->postForm('/admin/forum/structure/new?parent=' . $category, [
            'name'         => 'Fresh section',
            'desc'         => 'What it is about',
            'allow'        => 4,
            'section_type' => 1,
        ], $this->admin);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $section = ForumSection::query()->where('name', 'Fresh section')->firstOrFail();
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

        $this->postForm('/admin/forum/structure/' . $section . '/edit', $this->editFields([
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

        $this->postForm('/admin/forum/structure/' . $section . '/edit', $this->editFields([
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

        $response = $this->postForm('/admin/forum/structure/' . $category . '/edit', $this->editFields([
            'name'   => 'A category',
            'sort'   => 1,
            'parent' => $child,
        ]), $this->admin);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), 'The form comes back.');
        self::assertSame(0, (int) ForumSection::query()->findOrFail($category)->parent);
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
    private function editFields(array $fields): array
    {
        return $fields + [
            'description'      => '',
            'meta_description' => '',
            'meta_keywords'    => '',
            'access'           => 0,
            'section_type'     => 1,
        ];
    }
}
