<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Files\FileStore;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\UseCases\DeleteTopicUseCase;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The pictures of the text of the forum posts, held by the registry of the editor uploads: a post
 * takes only what its author uploaded, and gives it up with its text or when it is deleted for good.
 *
 * The suite never commits, so the files given up stay on the disk until tearDown: the tests look
 * at the registry, not at the files.
 */
final class ForumPostPicturesTest extends FunctionalTestCase
{
    /** @var list<int> */
    private array $uploadedFiles = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    private int $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = Fixture::insert('forum_sections', ['name' => 'A section', 'slug' => 'a-section', 'parent' => 0, 'section_type' => 1, 'sort' => 1]);
    }

    protected function tearDown(): void
    {
        $this->container()->get(FileStore::class)->deleteMany($this->uploadedFiles);
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->uploadedFiles = [];
        $this->temporaryFiles = [];

        parent::tearDown();
    }

    public function testANewTopicTakesThePictureItsAuthorUploadedAndNotThatOfSomebodyElse(): void
    {
        $author = FunctionalUserFactory::create();
        $own = $this->upload($author);
        $foreign = $this->upload(FunctionalUserFactory::create());

        $this->postForm('/forum/new-topic/' . $this->section . '/', [
            'th'     => 'A topic with pictures',
            'msg'    => '<p><img src="' . $own['url'] . '"></p><p><img src="' . $foreign['url'] . '"></p>',
            'token'  => $this->container()->get(ForumFormTokens::class)->issue(),
            'submit' => '1',
        ], $author);

        $message = (int) Capsule::table('forum_messages')->where('user_id', $author->id)->value('id');
        self::assertNotSame(0, $message, 'The topic was created.');
        self::assertSame(['owner_type' => 'forum_message', 'owner_id' => $message], $this->registration($own['id']));
        self::assertSame(['owner_type' => null, 'owner_id' => null], $this->registration($foreign['id']));
    }

    public function testEditingThePostWithoutThePictureGivesItUp(): void
    {
        $author = FunctionalUserFactory::create();
        $picture = $this->upload($author);
        $message = $this->post($author->id, $picture);

        $this->postForm('/forum/edit-post/' . $message . '/', ['msg' => '<p>Without the picture</p>', 'token' => $this->container()->get(ForumFormTokens::class)->issue(), 'submit' => '1'], FunctionalUserFactory::createSupervisor());

        self::assertSame('<p>Without the picture</p>', Capsule::table('forum_messages')->where('id', $message)->value('text'));
        self::assertNull($this->registration($picture['id']));
    }

    public function testDeletingTheTopicTakesThePicturesOfItsPosts(): void
    {
        $author = FunctionalUserFactory::create();
        $picture = $this->upload($author);
        $message = $this->post($author->id, $picture);
        $topic = (int) Capsule::table('forum_messages')->where('id', $message)->value('topic_id');

        $this->container()->get(DeleteTopicUseCase::class)->deleteTopic($topic);

        self::assertNull($this->registration($picture['id']));
    }

    public function testDeletingTheSectionWithItsTopicsTakesThePicturesOfThePosts(): void
    {
        $author = FunctionalUserFactory::create();
        $picture = $this->upload($author);
        $this->post($author->id, $picture);
        $owner = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::STRUCTURE_DESTROY]);

        $this->postForm('/admin/forum/sections/' . $this->section . '/delete', ['mode' => 'destroy'], $owner);

        self::assertSame(0, Capsule::table('forum_messages')->count());
        self::assertNull($this->registration($picture['id']));
    }

    public function testPurgingTheHiddenPostsTakesTheirPictures(): void
    {
        $author = FunctionalUserFactory::create();
        $picture = $this->upload($author);
        $message = $this->post($author->id, $picture, hidden: true);
        $purger = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, ForumPermissions::DELETED_VIEW, ForumPermissions::HIDDEN_PURGE]);

        $this->postForm('/admin/forum/hidden/purge', ['confirmed' => '1'], $purger);

        self::assertFalse(Capsule::table('forum_messages')->where('id', $message)->exists());
        self::assertNull($this->registration($picture['id']));
    }

    public function testThePicturesOfThePostsMoveIntoTheRegistry(): void
    {
        $author = FunctionalUserFactory::create();
        $topic = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'A topic', 'slug' => 'a-topic']);
        $shared = $this->file('forum_files/aa/bb/cc/shared.jpg');
        $shownOnly = $this->file('forum_files/aa/bb/cc/shown.jpg');
        $foreign = $this->file('guestbook/aa/bb/cc/foreign.jpg');
        $first = Fixture::insert('forum_messages', ['topic_id' => $topic, 'user_id' => $author->id, 'text' => '<p><img src="/upload/forum_files/aa/bb/cc/shared.jpg"></p>']);
        $second = Fixture::insert('forum_messages', ['topic_id' => $topic, 'user_id' => $author->id, 'text' => '<p><img src="/upload/forum_files/aa/bb/cc/shown.jpg"></p>']);
        $third = Fixture::insert('forum_messages', ['topic_id' => $topic, 'user_id' => $author->id, 'text' => '<p>Linked only</p>']);
        $db = $this->container()->get(ConnectionInterface::class);

        // The suite has dropped the table already; it comes back as a temporary one for the test.
        $db->execute(
            'CREATE TEMPORARY TABLE forum_message_files (id INTEGER NOT NULL PRIMARY KEY, message_id INTEGER NOT NULL, file_id INTEGER NOT NULL)'
        );
        try {
            $insert = 'INSERT INTO forum_message_files (id, message_id, file_id) VALUES (?, ?, ?)';
            $db->execute($insert, [1, $first, $shared]);
            $db->execute($insert, [2, $first, $foreign]);
            $db->execute($insert, [3, $third, $shared]);

            $migration = require ROOT_PATH . 'modules/johncms/forum/migrations/2026_11_14_210200_move_pictures_to_editor_uploads.php';
            $migration->bind($this->container()->get(SchemaInterface::class), $db);
            $migration->up();
        } finally {
            $db->execute(Capsule::connection()->getDriverName() === 'sqlite' ? 'DROP TABLE forum_message_files' : 'DROP TEMPORARY TABLE forum_message_files');
        }

        $rows = Capsule::table('editor_uploads')->where('owner_type', 'forum_message')->orderBy('owner_id')->get();
        self::assertSame([$first, $second, $third], $rows->pluck('owner_id')->map(intval(...))->all());
        self::assertSame($shared, (int) $rows[0]->file_id);
        self::assertSame($author->id, (int) $rows[0]->user_id);
        self::assertSame($shownOnly, (int) $rows[1]->file_id);
        self::assertNotSame($shared, (int) $rows[2]->file_id, 'The second post holds a copy of its own.');
        self::assertSame('forum_files/aa/bb/cc/shared.jpg', Capsule::table('files')->where('id', $rows[2]->file_id)->value('path'));
        self::assertFalse(Capsule::table('editor_uploads')->where('file_id', $foreign)->exists(), 'A file of another directory is not taken.');
    }

    /**
     * A post that holds the picture, as the form would have left it.
     *
     * @param array{id: int, url: string} $picture
     */
    private function post(int $userId, array $picture, bool $hidden = false): int
    {
        $topic = Fixture::insert('forum_topic', ['section_id' => $this->section, 'name' => 'A topic', 'slug' => 'a-topic-' . bin2hex(random_bytes(3))]);
        $message = Fixture::insert('forum_messages', [
            'topic_id' => $topic,
            'user_id'  => $userId,
            'date'     => time(),
            'text'     => '<p><img src="' . $picture['url'] . '"></p>',
            'deleted'  => $hidden ? 1 : 0,
        ]);
        $taken = Capsule::table('editor_uploads')->where('file_id', $picture['id'])->update(['owner_type' => 'forum_message', 'owner_id' => $message]);
        self::assertSame(1, $taken, 'The upload is registered.');

        return $message;
    }

    /**
     * @return array{owner_type: ?string, owner_id: ?int}|null
     */
    private function registration(int $fileId): ?array
    {
        $row = Capsule::table('editor_uploads')->where('file_id', $fileId)->first();
        if ($row === null) {
            return null;
        }

        return [
            'owner_type' => $row->owner_type,
            'owner_id'   => $row->owner_id === null ? null : (int) $row->owner_id,
        ];
    }

    private function file(string $path): int
    {
        return Fixture::insert('files', ['storage' => 'local', 'name' => basename($path), 'path' => $path]);
    }

    /**
     * @return array{id: int, url: string}
     */
    private function upload(User $user): array
    {
        $image = imagecreatetruecolor(3, 3);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        $path = sys_get_temp_dir() . '/johncms-forum-' . bin2hex(random_bytes(6)) . '.png';
        imagepng($image, $path);
        imagedestroy($image);
        $this->temporaryFiles[] = $path;

        $response = $this->handleRequest(
            '/forum/upload_file',
            'POST',
            $this->withCsrfToken(),
            cookies: $this->actingAs($user),
            files: ['upload' => new UploadedFile($path, 'picture.png', 'image/png', null, true)]
        );
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $stored = json_decode((string) $response->getContent(), true);
        self::assertIsArray($stored);
        self::assertArrayHasKey('id', $stored, (string) $response->getContent());
        $this->uploadedFiles[] = (int) $stored['id'];

        return ['id' => (int) $stored['id'], 'url' => (string) $stored['url']];
    }
}
