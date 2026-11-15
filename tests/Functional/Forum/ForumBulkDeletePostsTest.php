<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Posts chosen on the page of a topic are hidden together. A topic is never left in sight with no
 * post to show: choosing every post hides the topic instead, and its pages keep opening. What was
 * hidden takes its attached files out of the public list of files.
 */
final class ForumBulkDeletePostsTest extends FunctionalTestCase
{
    private int $section;
    private int $topic;
    private int $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author'])->id;
        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => $this->author,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
    }

    public function testTheChosenPostsAreHidden(): void
    {
        $first = $this->post('first');
        $second = $this->post('second');

        $this->deleteChosen([$second]);

        self::assertFalse($this->isHidden($first));
        self::assertTrue($this->isHidden($second));
        self::assertFalse($this->topic()->deleted);
        self::assertSame(1, $this->topic()->post_count);
    }

    public function testChoosingEveryPostHidesTheTopicInstead(): void
    {
        $first = $this->post('first');
        $second = $this->post('second');

        $response = $this->deleteChosen([$first, $second]);

        self::assertStringContainsString('href="/forum/talk/"', (string) $response->getContent(), 'Leads back to the section.');
        self::assertTrue($this->topic()->deleted);
        self::assertFalse($this->isHidden($first), 'The posts stay as they were, so restoring the topic brings them back.');
        self::assertFalse($this->isHidden($second));
    }

    public function testThePostsLeftInSightCountWhenSomeAreHiddenAlready(): void
    {
        $hidden = $this->post('hidden', ['deleted' => 1, 'deleted_by' => 'Alex']);
        $last = $this->post('last');

        $this->deleteChosen([$hidden, $last]);

        self::assertTrue($this->topic()->deleted, 'Nothing in sight would be left.');
        self::assertSame('Alex', ForumMessage::query()->findOrFail($hidden)->deleted_by, 'A post hidden before is not hidden again.');
    }

    public function testTheSectionStillOpensAndTheHiddenTopicIsNotFound(): void
    {
        $this->post('first');
        $this->post('second');
        $this->deleteChosen(ForumMessage::query()->where('topic_id', $this->topic)->pluck('id')->all());

        self::assertSame(200, $this->handleRequest('/forum/talk/')->getStatusCode());
        self::assertSame(200, $this->handleRequest('/forum/talk/', cookies: $this->actingAs(FunctionalUserFactory::create()))->getStatusCode());
        self::assertSame(404, $this->handleRequest('/forum/talk/a-topic-' . $this->topic . '/')->getStatusCode(), 'The hidden topic is not found, not broken.');
    }

    public function testTheFilesOfAHiddenTopicLeaveTheListOfFiles(): void
    {
        $post = $this->post('A secret post with a file');
        $file = $this->file($post, 1);

        self::assertStringContainsString('A secret post with a file', $this->filesListOfAGuest(), 'Listed while the topic is in sight.');

        $this->deleteChosen([$post]);

        self::assertTrue($this->topic()->deleted);
        self::assertTrue($this->isFileHidden($file));
        self::assertStringNotContainsString('A secret post with a file', $this->filesListOfAGuest());
    }

    public function testTheFilesOfHiddenPostsLeaveTheListOfFiles(): void
    {
        $kept = $this->post('A post that stays');
        $hidden = $this->post('A hidden post with a file');
        $keptFile = $this->file($kept, 1);
        $hiddenFile = $this->file($hidden, 1);

        $this->deleteChosen([$hidden]);

        self::assertFalse($this->isFileHidden($keptFile));
        self::assertTrue($this->isFileHidden($hiddenFile));
        self::assertStringNotContainsString('A hidden post with a file', $this->filesListOfAGuest());
    }

    /**
     * @param list<int> $ids
     */
    private function deleteChosen(array $ids): Response
    {
        return $this->postForm(
            '/forum/bulk-delete-posts/' . $this->topic . '/',
            ['confirm' => '1', 'ids' => array_map('strval', $ids)],
            FunctionalUserFactory::createSupervisor(),
        );
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

    private function file(int $post, int $type): int
    {
        return Fixture::insert('cms_forum_files', [
            'cat'      => 0,
            'subcat'   => $this->section,
            'topic'    => $this->topic,
            'post'     => $post,
            'time'     => time(),
            'filename' => 'file-' . $post . '.zip',
            'filetype' => $type,
            'del'      => 0,
        ]);
    }

    private function isFileHidden(int $file): bool
    {
        return (bool) ForumFile::query()->withoutGlobalScopes()->findOrFail($file)->del;
    }

    private function filesListOfAGuest(): string
    {
        return (string) $this->handleRequest('/forum/files/?do=1')->getContent();
    }

    private function isHidden(int $post): bool
    {
        return (bool) ForumMessage::query()->findOrFail($post)->deleted;
    }

    private function topic(): ForumTopic
    {
        /** @var ForumTopic */
        return ForumTopic::query()->withoutGlobalScopes()->findOrFail($this->topic);
    }
}
