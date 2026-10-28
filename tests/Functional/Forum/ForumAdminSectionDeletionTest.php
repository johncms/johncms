<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as DB;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Deleting a section of the forum: an empty one goes at once, the sections or the topics of a full
 * one go somewhere else first, and only the owner of the structure deletes the topics with it.
 */
final class ForumAdminSectionDeletionTest extends FunctionalTestCase
{
    public function testAnEmptySectionIsDeleted(): void
    {
        $section = $this->section('Empty', 0, 1, 1);

        $response = $this->postForm('/admin/forum/structure/' . $section . '/delete', [], $this->admin());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/forum', $response->headers->get('Location'));
        self::assertFalse(ForumSection::query()->whereKey($section)->exists());
    }

    public function testTheSectionsOfADeletedSectionGoToTheEndOfAnother(): void
    {
        $deleted = $this->section('Deleted', 0, 0, 1);
        $target = $this->section('Target', 0, 0, 2);
        $this->section('Already there', $target, 1, 4);
        $child = $this->section('A child', $deleted, 1, 1);
        $file = Fixture::insert('cms_forum_files', ['cat' => $deleted, 'subcat' => $child, 'filename' => 'a.txt']);

        $this->postForm('/admin/forum/structure/' . $deleted . '/delete', ['category' => $target], $this->admin());

        self::assertFalse(ForumSection::query()->whereKey($deleted)->exists());
        $moved = ForumSection::query()->findOrFail($child);
        self::assertSame($target, (int) $moved->parent);
        self::assertSame(5, $moved->sort);
        self::assertSame($target, (int) $this->column('cms_forum_files', $file, 'cat'));
    }

    public function testTheTopicsOfADeletedSectionGoToAnother(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $deleted = $this->section('Deleted', $category, 1, 1);
        $target = $this->section('Target', $category, 1, 2);
        $topic = Fixture::insert('forum_topic', ['section_id' => $deleted, 'name' => 'A topic', 'slug' => 'a-topic']);
        $file = Fixture::insert('cms_forum_files', ['cat' => $category, 'subcat' => $deleted, 'topic' => $topic, 'filename' => 'a.txt']);

        $this->postForm('/admin/forum/structure/' . $deleted . '/delete', ['subcat' => $target], $this->admin());

        self::assertFalse(ForumSection::query()->whereKey($deleted)->exists());
        self::assertSame($target, (int) $this->column('forum_topic', $topic, 'section_id'));
        self::assertSame($target, (int) $this->column('cms_forum_files', $file, 'subcat'));
    }

    public function testDeletingTheTopicsWithTheSectionTakesMoreThanOpeningThePanel(): void
    {
        $section = $this->section('Full', 0, 1, 1);
        Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic']);

        $this->postForm('/admin/forum/structure/' . $section . '/delete', ['delete' => 1], $this->admin());

        self::assertTrue(ForumSection::query()->whereKey($section)->exists());
    }

    public function testTheOwnerOfTheStructureDeletesTheTopicsWithTheSection(): void
    {
        $owner = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::STRUCTURE_DESTROY]);
        $section = $this->section('Full', 0, 1, 1);
        $topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic']);
        $message = Fixture::insert('forum_messages', ['topic_id' => $topic, 'text' => 'Hello']);

        $this->postForm('/admin/forum/structure/' . $section . '/delete', ['delete' => 1], $owner);

        self::assertFalse(ForumSection::query()->whereKey($section)->exists());
        self::assertNull($this->column('forum_topic', $topic, 'id'));
        self::assertNull($this->column('forum_messages', $message, 'id'));
    }

    /**
     * A moderator sees what is hidden, an administrator of the panel may not; the structure moves
     * and deletes it all the same, or a hidden topic would stay behind without a section.
     */
    public function testHiddenTopicsAndFilesMoveWithTheOthers(): void
    {
        $category = $this->section('A category', 0, 0, 1);
        $deleted = $this->section('Deleted', $category, 1, 1);
        $target = $this->section('Target', $category, 1, 2);
        $topic = Fixture::insert('forum_topic', ['section_id' => $deleted, 'name' => 'A hidden topic', 'slug' => 'a-hidden-topic', 'deleted' => 1]);
        $file = Fixture::insert('cms_forum_files', ['cat' => $category, 'subcat' => $deleted, 'topic' => $topic, 'filename' => 'a.txt', 'del' => 1]);

        $this->postForm('/admin/forum/structure/' . $deleted . '/delete', ['subcat' => $target], $this->admin());

        self::assertFalse(ForumSection::query()->whereKey($deleted)->exists());
        self::assertSame($target, (int) $this->column('forum_topic', $topic, 'section_id'));
        self::assertSame($target, (int) $this->column('cms_forum_files', $file, 'subcat'));
    }

    public function testHiddenTopicsAndPostsGoWithADeletedSection(): void
    {
        $owner = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::STRUCTURE_DESTROY]);
        $section = $this->section('Full', 0, 1, 1);
        $topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A hidden topic', 'slug' => 'a-hidden-topic', 'deleted' => 1]);
        $message = Fixture::insert('forum_messages', ['topic_id' => $topic, 'text' => 'Hello', 'deleted' => 1]);

        $this->postForm('/admin/forum/structure/' . $section . '/delete', ['delete' => 1], $owner);

        self::assertFalse(ForumSection::query()->whereKey($section)->exists());
        self::assertNull($this->column('forum_topic', $topic, 'id'));
        self::assertNull($this->column('forum_messages', $message, 'id'));
    }

    private function admin(): User
    {
        return FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
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
     * A column of a row as the table holds it, past the scopes of the models.
     */
    private function column(string $table, int $id, string $column): mixed
    {
        return DB::table($table)->where('id', $id)->value($column);
    }
}
