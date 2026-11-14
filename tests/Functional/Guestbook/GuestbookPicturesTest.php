<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Files\FileStore;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The pictures of the guestbook entries, held by the registry of the editor uploads: an entry
 * takes only what its author uploaded, and gives it up with its text.
 *
 * The suite never commits, so the files given up stay on the disk until tearDown: the tests look
 * at the registry, not at the files.
 */
final class GuestbookPicturesTest extends FunctionalTestCase
{
    /** @var list<int> */
    private array $uploadedFiles = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        // The rows go away with the transaction, the files on the disk do not.
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

    public function testAnEntryTakesThePictureItsAuthorUploaded(): void
    {
        $author = FunctionalUserFactory::create();
        $picture = $this->upload($author);
        self::assertSame(['owner_type' => null, 'owner_id' => null], $this->registration($picture['id']));

        $this->postForm('/guestbook/', ['message' => $this->withPicture($picture['url'])], $author);

        $entry = (int) Capsule::table('guest')->value('id');
        self::assertSame(['owner_type' => 'guestbook', 'owner_id' => $entry], $this->registration($picture['id']));
    }

    public function testThePictureOfSomebodyElseIsShownButNeverTaken(): void
    {
        $uploader = FunctionalUserFactory::create();
        $picture = $this->upload($uploader);

        $this->postForm('/guestbook/', ['message' => $this->withPicture($picture['url'])], FunctionalUserFactory::create());

        self::assertSame(1, Capsule::table('guest')->count());
        self::assertSame(['owner_type' => null, 'owner_id' => null], $this->registration($picture['id']));
    }

    public function testEditingWithoutThePictureGivesItUpAndTheReplyKeepsIt(): void
    {
        $author = FunctionalUserFactory::create();
        $moderator = FunctionalUserFactory::createSupervisor();
        $picture = $this->upload($author);
        $this->postForm('/guestbook/', ['message' => $this->withPicture($picture['url'])], $author);
        $entry = (int) Capsule::table('guest')->value('id');

        $this->postForm('/guestbook/otvet?id=' . $entry, ['message' => '<p>A reply of the staff</p>'], $moderator);
        self::assertSame(['owner_type' => 'guestbook', 'owner_id' => $entry], $this->registration($picture['id']));

        $this->postForm('/guestbook/edit?id=' . $entry, ['message' => '<p>No picture any more</p>'], $moderator);
        self::assertNull($this->registration($picture['id']));
    }

    public function testDeletingAndClearingTakeThePicturesWithTheEntries(): void
    {
        $author = FunctionalUserFactory::create();
        $moderator = FunctionalUserFactory::createSupervisor();
        $first = $this->upload($author);
        $this->postForm('/guestbook/', ['message' => $this->withPicture($first['url'])], $author);
        $entry = (int) Capsule::table('guest')->value('id');

        $this->postForm('/guestbook/delpost', ['id' => (string) $entry, 'confirmed' => '1'], $moderator);

        self::assertNull($this->registration($first['id']));

        // A second post would be refused as a flood: the entry and its picture are put in place.
        $second = $this->upload($author);
        $other = Fixture::insert('guest', ['adm' => 0, 'user_id' => $author->id, 'name' => 'Author', 'text' => $this->withPicture($second['url']), 'time' => time()]);
        Capsule::table('editor_uploads')->where('file_id', $second['id'])->update(['owner_type' => 'guestbook', 'owner_id' => $other]);

        $this->postForm('/guestbook/clean', ['cl' => '2', 'confirmed' => '1'], $moderator);

        self::assertSame(0, Capsule::table('guest')->count());
        self::assertNull($this->registration($second['id']));
    }

    public function testThePicturesOfTheEntriesMoveIntoTheRegistry(): void
    {
        $author = FunctionalUserFactory::create();
        $shared = $this->file('guestbook/aa/bb/cc/shared.jpg');
        $shownOnly = $this->file('guestbook/aa/bb/cc/shown.jpg');
        $foreign = $this->file('forum_files/aa/bb/cc/foreign.jpg');
        $db = $this->container()->get(ConnectionInterface::class);

        // A temporary table of the same name hides the real one, whose column is already dropped.
        $db->execute(
            'CREATE TEMPORARY TABLE guest (id INTEGER NOT NULL PRIMARY KEY, user_id INTEGER NOT NULL, text TEXT NOT NULL,'
            . ' otvet TEXT NOT NULL, attached_files TEXT NULL)'
        );
        try {
            $insert = 'INSERT INTO guest (id, user_id, text, otvet, attached_files) VALUES (?, ?, ?, ?, ?)';
            $db->execute($insert, [1, $author->id, '<p><img src="/upload/guestbook/aa/bb/cc/shared.jpg"></p>', '', json_encode([$shared, $foreign, 999999])]);
            $db->execute($insert, [2, $author->id, '<p>Text</p>', '<img src="https://johncms.loc/upload/guestbook/aa/bb/cc/shown.jpg">', null]);
            $db->execute($insert, [3, 0, '<p><img src="/upload/guestbook/aa/bb/cc/shared.jpg"></p>', '', json_encode([$shared])]);

            $migration = require ROOT_PATH . 'modules/johncms/guestbook/migrations/2026_11_14_205319_move_pictures_to_editor_uploads.php';
            $migration->bind($this->container()->get(SchemaInterface::class), $db);
            $migration->up();
        } finally {
            $db->execute(Capsule::connection()->getDriverName() === 'sqlite' ? 'DROP TABLE temp.guest' : 'DROP TEMPORARY TABLE guest');
        }

        $byEntry = Capsule::table('editor_uploads')->where('owner_type', 'guestbook')->orderBy('owner_id')->get();
        self::assertSame([1, 2, 3], $byEntry->pluck('owner_id')->map(intval(...))->all());
        self::assertSame($shared, (int) $byEntry[0]->file_id);
        self::assertSame($author->id, (int) $byEntry[0]->user_id);
        self::assertSame($shownOnly, (int) $byEntry[1]->file_id);

        $copy = (int) $byEntry[2]->file_id;
        self::assertNotSame($shared, $copy, 'The second entry holds a copy of its own.');
        self::assertSame('guestbook/aa/bb/cc/shared.jpg', Capsule::table('files')->where('id', $copy)->value('path'));
        self::assertFalse(Capsule::table('editor_uploads')->where('file_id', $foreign)->exists(), 'A file of another directory is not taken.');
    }

    private function withPicture(string $url): string
    {
        return '<p>Look</p><p><img src="' . $url . '"></p>';
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
        $path = sys_get_temp_dir() . '/johncms-guestbook-' . bin2hex(random_bytes(6)) . '.png';
        imagepng($image, $path);
        imagedestroy($image);
        $this->temporaryFiles[] = $path;

        $response = $this->handleRequest(
            '/guestbook/upload_file',
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
