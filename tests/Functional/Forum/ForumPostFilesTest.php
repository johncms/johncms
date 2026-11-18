<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Http\UploadedFileDTO;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;
use Johncms\Storage\StorageException;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The files of a post are chosen in the form that writes it — a post, an answer, a new topic or an
 * edit — and are sent with it. All or nothing: one file refused, and neither the post nor any file
 * is saved; the form comes back with the text and a working token, and the files are chosen anew.
 *
 * The suite never commits, so the files written stay on the disk: tearDown deletes them.
 */
final class ForumPostFilesTest extends FunctionalTestCase
{
    private int $section;
    private int $topic;
    private User $author;
    private User $writer;

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = FunctionalUserFactory::create(['name' => 'Author']);
        $this->writer = FunctionalUserFactory::create(['name' => 'Writer']);
        $this->section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $this->topic = Fixture::insert('forum_topic', [
            'section_id' => $this->section,
            'name'       => 'A topic',
            'slug'       => 'a-topic',
            'user_id'    => $this->author->id,
            'user_name'  => 'Author',
            'deleted'    => 0,
            'closed'     => 0,
            'pinned'     => 0,
        ]);
        $this->post($this->author, '<p>A question</p>', time() - 7200);
    }

    protected function tearDown(): void
    {
        $storage = $this->container()->get(ForumAttachmentStorage::class);
        foreach (Capsule::table('cms_forum_files')->pluck('filename')->all() as $name) {
            $storage->delete((string) $name);
        }
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->temporaryFiles = [];

        parent::tearDown();
    }

    public function testAPostIsSavedWithItsFiles(): void
    {
        $response = $this->send('/forum/new-message/' . $this->topic . '/', [$this->file('notes.txt'), $this->file('photo.jpg')]);

        self::assertSame(302, $response->getStatusCode());
        $post = $this->latestOfWriter();
        self::assertSame(['notes.txt', 'photo.jpg'], $this->filesOf($post));
        self::assertTrue($this->container()->get(ForumAttachmentStorage::class)->exists('notes.txt'));
        self::assertSame([4, 5], Capsule::table('cms_forum_files')->where('post', $post)->orderBy('id')->pluck('filetype')->map(intval(...))->all(), 'Grouped as a document and a picture.');
    }

    public function testOneFileRefusedSavesNeitherThePostNorTheOthers(): void
    {
        $response = $this->send('/forum/new-message/' . $this->topic . '/', [$this->file('notes.txt'), $this->file('virus.exe.bat')]);

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse(Capsule::table('forum_messages')->where('user_id', $this->writer->id)->exists());
        self::assertSame(0, Capsule::table('cms_forum_files')->count());
        self::assertFalse($this->container()->get(ForumAttachmentStorage::class)->exists('notes.txt'));
        $page = (string) $response->getContent();
        self::assertStringContainsString('virus.exe.bat', $page, 'The field says which file.');
        self::assertStringContainsString('A text with files', $page, 'The text stays in the form.');
        self::assertMatchesRegularExpression('~name="token" value="[0-9a-f]{32}"~', $page);
    }

    public function testAFileThatCannotBeWrittenTakesBackThoseWrittenBeforeIt(): void
    {
        $post = $this->post($this->writer, '<p>Mine</p>', time());
        $written = $this->file('notes.txt');

        try {
            $this->container()->get(ForumPostFiles::class)->store($post, [
                new UploadedFileDTO('notes.txt', 'text/plain', 10, $written->getPathname(), UPLOAD_ERR_OK),
                new UploadedFileDTO('lost.txt', 'text/plain', 10, '/nonexistent/lost.txt', UPLOAD_ERR_OK),
            ]);
            self::fail('A file that cannot be read was stored.');
        } catch (StorageException) {
        }

        self::assertFalse($this->container()->get(ForumAttachmentStorage::class)->exists('notes.txt'), 'The file written first is deleted again.');
    }

    public function testAPostHoldsNoMoreFilesThanTheSettingAllows(): void
    {
        $response = $this->withConfig(
            ['forum' => ['settings' => ['max_files_per_post' => 1]]],
            fn (): Response => $this->send('/forum/new-message/' . $this->topic . '/', [$this->file('one.txt'), $this->file('two.txt')])
        );

        self::assertSame(422, $response->getStatusCode());
        self::assertFalse(Capsule::table('forum_messages')->where('user_id', $this->writer->id)->exists());
    }

    public function testAnAnswerFromItsOwnPageIsSavedWithItsFiles(): void
    {
        $answered = (int) Capsule::table('forum_messages')->where('user_id', $this->author->id)->value('id');

        self::assertSame(302, $this->send('/forum/reply-message/' . $answered . '/', [$this->file('answer.txt')])->getStatusCode());
        self::assertSame(['answer.txt'], $this->filesOf($this->latestOfWriter()));
    }

    public function testANewTopicIsSavedWithTheFilesOfItsFirstPost(): void
    {
        $response = $this->send('/forum/new-topic/' . $this->section . '/', [$this->file('topic.txt')], ['th' => 'With a file']);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame(['topic.txt'], $this->filesOf($this->latestOfWriter()));
    }

    public function testAnEditCountsTheFilesThePostHoldsAlready(): void
    {
        $post = $this->post($this->writer, '<p>Mine</p>', time());

        $response = $this->withConfig(['forum' => ['settings' => ['max_files_per_post' => 2]]], function () use ($post): Response {
            self::assertSame(302, $this->send('/forum/edit-post/' . $post . '/', [$this->file('first.txt')])->getStatusCode());

            return $this->send('/forum/edit-post/' . $post . '/', [$this->file('second.txt'), $this->file('third.txt')]);
        });

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(['first.txt'], $this->filesOf($post));
    }

    public function testAnEditSentTwiceAttachesItsFilesOnce(): void
    {
        $post = $this->post($this->writer, '<p>Mine</p>', time());
        $token = $this->container()->get(ForumFormTokens::class)->issue();

        $this->send('/forum/edit-post/' . $post . '/', [$this->file('once.txt')], ['token' => $token]);
        $this->send('/forum/edit-post/' . $post . '/', [$this->file('once.txt')], ['token' => $token]);

        self::assertCount(1, $this->filesOf($post));
    }

    public function testAFileOfAHiddenPostIsHiddenWithIt(): void
    {
        $post = $this->post($this->writer, '<p>Something regretted</p>', time());
        Capsule::table('forum_messages')->where('id', $post)->update(['deleted' => 1, 'deleted_by' => 'Alex']);
        $moderator = FunctionalUserFactory::createSupervisor();

        $response = $this->handleRequest(
            '/forum/edit-post/' . $post . '/',
            'POST',
            $this->withCsrfToken([
                'msg'    => '<p>Something regretted, edited</p>',
                'token'  => $this->container()->get(ForumFormTokens::class)->issue(),
                'submit' => '1',
            ]),
            cookies: $this->actingAs($moderator),
            files: ['files' => [$this->file('regret.txt')]],
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame(1, (int) Capsule::table('cms_forum_files')->where('post', $post)->value('del'));
        self::assertStringNotContainsString('>regret.txt</a>', (string) $this->handleRequest('/forum/files/?do=4')->getContent(), 'A guest does not see it.');
    }

    public function testTheFormsNoLongerOfferToAttachAFileAfterwards(): void
    {
        $page = (string) $this->handleRequest('/forum/talk/a-topic-' . $this->topic . '/', cookies: $this->actingAs($this->writer))->getContent();

        self::assertStringContainsString('enctype="multipart/form-data"', $page);
        self::assertStringContainsString('name="files[]" multiple', $page);
        self::assertStringNotContainsString('name="addfiles"', $page);
        self::assertSame(404, $this->handleRequest('/forum/addfile/1/', cookies: $this->actingAs($this->writer))->getStatusCode());
    }

    /**
     * A form sent with its files, as the page gives it: with a one-time token it issued.
     *
     * @param list<UploadedFile>    $files
     * @param array<string, string> $fields
     */
    private function send(string $uri, array $files, array $fields = []): Response
    {
        // The antiflood would refuse a second form of the same test on its own.
        Capsule::table('users')->where('id', $this->writer->id)->update(['lastpost' => 0]);

        return $this->handleRequest(
            $uri,
            'POST',
            $this->withCsrfToken($fields + [
                'msg'    => '<p>A text with files ' . bin2hex(random_bytes(3)) . '</p>',
                'token'  => $this->container()->get(ForumFormTokens::class)->issue(),
                'submit' => '1',
            ]),
            cookies: $this->actingAs($this->writer),
            files: ['files' => $files],
        );
    }

    private function file(string $name): UploadedFile
    {
        $path = sys_get_temp_dir() . '/johncms-forum-file-' . bin2hex(random_bytes(6));
        file_put_contents($path, 'content of ' . $name);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @return list<string>
     */
    private function filesOf(int $post): array
    {
        return array_map(strval(...), Capsule::table('cms_forum_files')->where('post', $post)->orderBy('id')->pluck('filename')->all());
    }

    private function latestOfWriter(): int
    {
        return (int) Capsule::table('forum_messages')->where('user_id', $this->writer->id)->orderByDesc('id')->value('id');
    }

    private function post(User $user, string $text, int $date): int
    {
        $id = Fixture::insert('forum_messages', [
            'topic_id'  => $this->topic,
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'date'      => $date,
            'text'      => $text,
        ]);
        $this->container()->get(ForumTopicStatsRecalculator::class)->recalculate($this->topic);

        return $id;
    }
}
