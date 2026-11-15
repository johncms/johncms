<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * A post is deleted from the dialog of the topic page and restored by a form: opening an address
 * changes nothing, and a deletion the dialog did not confirm is refused. Deleting for good is a
 * choice of the dialog, made only with the permission for it.
 */
final class ForumPostDeletionTest extends FunctionalTestCase
{
    private int $topic;
    private int $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author'])->id;
        $section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => $this->author,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    public function testAPostIsHiddenOnlyByAConfirmedForm(): void
    {
        $this->post('first');
        $post = $this->post('second');
        $moderator = FunctionalUserFactory::createSupervisor();

        $this->handleRequest('/forum/delete-post/' . $post . '/', cookies: $this->actingAs($moderator));
        self::assertFalse($this->isHidden($post), 'Opening the address changes nothing.');

        self::assertSame(409, $this->postForm('/forum/delete-post/' . $post . '/', [], $moderator)->getStatusCode());
        self::assertFalse($this->isHidden($post));

        $hidden = $this->postForm('/forum/delete-post/' . $post . '/', ['confirmed' => '1'], $moderator);

        self::assertSame(302, $hidden->getStatusCode());
        self::assertTrue($this->isHidden($post), 'Without a choice the post is hidden, not deleted.');
    }

    public function testAPostIsDeletedForGoodWhenThatIsChosen(): void
    {
        $this->post('first');
        $post = $this->post('second');

        $this->postForm('/forum/delete-post/' . $post . '/', ['confirmed' => '1', 'action' => 'delete'], FunctionalUserFactory::createSupervisor());

        self::assertFalse(ForumMessage::query()->whereKey($post)->exists());
    }

    public function testDeletingForGoodTakesThePermissionForIt(): void
    {
        $this->post('first');
        $post = $this->post('second');
        $moderator = FunctionalUserFactory::createWithPermissions(['forum.topic.moderate', 'forum.deleted.view']);

        $this->postForm('/forum/delete-post/' . $post . '/', ['confirmed' => '1', 'action' => 'delete'], $moderator);

        self::assertTrue(ForumMessage::query()->whereKey($post)->exists(), 'Without the permission the post is only hidden.');
        self::assertTrue($this->isHidden($post));
    }

    public function testDeletingTheLastPostInSightHidesTheTopic(): void
    {
        $post = $this->post('the only one');

        $this->postForm('/forum/delete-post/' . $post . '/', ['confirmed' => '1'], FunctionalUserFactory::createSupervisor());

        self::assertTrue((bool) ForumTopic::query()->withoutGlobalScopes()->findOrFail($this->topic)->deleted);
    }

    public function testAPostIsRestoredOnlyByAForm(): void
    {
        $this->post('first');
        $post = $this->post('second', ['deleted' => 1, 'deleted_by' => 'Alex']);
        $moderator = FunctionalUserFactory::createSupervisor();

        $this->handleRequest('/forum/restore-post/' . $post . '/', cookies: $this->actingAs($moderator));
        self::assertTrue($this->isHidden($post), 'Opening the address changes nothing.');

        self::assertSame(302, $this->postForm('/forum/restore-post/' . $post . '/', [], $moderator)->getStatusCode());
        self::assertFalse($this->isHidden($post));
    }

    public function testAFileOfAPostIsDeletedOnlyByAConfirmedForm(): void
    {
        $post = $this->post('with a file');
        $file = Fixture::insert('cms_forum_files', [
            'topic'    => $this->topic,
            'post'     => $post,
            'time'     => time(),
            'filename' => 'missing-on-disk.zip',
            'filetype' => 1,
            'del'      => 0,
        ]);
        $moderator = FunctionalUserFactory::createSupervisor();
        $address = '/forum/delete-post-file/' . $post . '/' . $file . '/';

        $this->handleRequest($address, cookies: $this->actingAs($moderator));
        self::assertTrue(ForumFile::query()->withoutGlobalScopes()->whereKey($file)->exists(), 'Opening the address changes nothing.');

        self::assertSame(409, $this->postForm($address, [], $moderator)->getStatusCode());
        self::assertTrue(ForumFile::query()->withoutGlobalScopes()->whereKey($file)->exists());

        self::assertSame(302, $this->postForm($address, ['confirmed' => '1'], $moderator)->getStatusCode());
        self::assertFalse(ForumFile::query()->withoutGlobalScopes()->whereKey($file)->exists());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function post(string $text, array $attributes = []): int
    {
        $id = Fixture::insert('forum_messages', $attributes + [
            'topic_id'  => $this->topic,
            'user_id'   => $this->author,
            'user_name' => 'Author',
            'date'      => time(),
            'text'      => $text,
        ]);
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($this->topic);

        return $id;
    }

    private function isHidden(int $post): bool
    {
        return (bool) ForumMessage::query()->findOrFail($post)->deleted;
    }
}
