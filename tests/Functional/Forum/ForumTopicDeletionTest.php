<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * A topic is deleted from the dialog of its page: hidden by default, deleted for good only when
 * that is chosen with the permission for it. Its files go out of sight with it and come back when
 * it is restored, except those of posts hidden on their own.
 */
final class ForumTopicDeletionTest extends FunctionalTestCase
{
    private int $section;
    private int $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => 1,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    public function testATopicIsHiddenOnlyByAConfirmedForm(): void
    {
        $file = $this->file($this->post());
        $moderator = FunctionalUserFactory::createSupervisor();

        $this->handleRequest('/forum/delete-topic/' . $this->topic . '/', cookies: $this->actingAs($moderator));
        self::assertFalse($this->fresh()->deleted, 'Opening the address changes nothing.');

        self::assertSame(409, $this->postForm('/forum/delete-topic/' . $this->topic . '/', [], $moderator)->getStatusCode());
        self::assertFalse($this->fresh()->deleted);

        $hidden = $this->postForm('/forum/delete-topic/' . $this->topic . '/', ['confirmed' => '1'], $moderator);

        self::assertSame('/forum/talk/', $hidden->headers->get('Location'));
        self::assertTrue($this->fresh()->deleted, 'Without a choice the topic is hidden, not deleted.');
        self::assertTrue($this->isFileHidden($file));
    }

    public function testATopicIsDeletedForGoodWhenThatIsChosen(): void
    {
        $this->post();

        $this->postForm('/forum/delete-topic/' . $this->topic . '/', ['confirmed' => '1', 'del' => '2'], FunctionalUserFactory::createSupervisor());

        self::assertFalse(ForumTopic::query()->withoutGlobalScopes()->whereKey($this->topic)->exists());
    }

    public function testDeletingForGoodTakesThePermissionForIt(): void
    {
        $this->post();
        $moderator = FunctionalUserFactory::createWithPermissions(['forum.topic.moderate']);

        $this->postForm('/forum/delete-topic/' . $this->topic . '/', ['confirmed' => '1', 'del' => '2'], $moderator);

        self::assertTrue($this->fresh()->deleted, 'Without the permission the topic is only hidden.');
    }

    public function testRestoringATopicBringsBackTheFilesOfThePostsInSight(): void
    {
        $shown = $this->file($this->post());
        $ownHidden = $this->file($this->post(['deleted' => 1, 'deleted_by' => 'Alex']));
        $moderator = FunctionalUserFactory::createWithPermissions(['forum.topic.moderate', 'forum.deleted.view']);

        $this->postForm('/forum/delete-topic/' . $this->topic . '/', ['confirmed' => '1'], $moderator);
        self::assertTrue($this->isFileHidden($shown));

        $this->postForm('/forum/restore-topic/' . $this->topic . '/', [], $moderator);

        self::assertFalse($this->fresh()->deleted);
        self::assertFalse($this->isFileHidden($shown), 'Back with its post.');
        self::assertTrue($this->isFileHidden($ownHidden), 'The file of a post hidden on its own stays hidden.');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function post(array $attributes = []): int
    {
        return Fixture::insert('forum_messages', $attributes + [
            'topic_id'  => $this->topic,
            'user_id'   => 1,
            'user_name' => 'Author',
            'date'      => time(),
            'text'      => 'A post',
        ]);
    }

    private function file(int $post): int
    {
        return Fixture::insert('cms_forum_files', [
            'cat'      => 0,
            'subcat'   => $this->section,
            'topic'    => $this->topic,
            'post'     => $post,
            'time'     => time(),
            'filename' => 'file-' . $post . '.zip',
            'filetype' => 1,
            'del'      => 0,
        ]);
    }

    private function isFileHidden(int $file): bool
    {
        return (bool) ForumFile::query()->withoutGlobalScopes()->findOrFail($file)->del;
    }

    private function fresh(): ForumTopic
    {
        /** @var ForumTopic */
        return ForumTopic::query()->withoutGlobalScopes()->findOrFail($this->topic);
    }
}
