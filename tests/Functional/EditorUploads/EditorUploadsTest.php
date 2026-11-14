<?php

declare(strict_types=1);

namespace Tests\Functional\EditorUploads;

use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\CleanupEditorUploadsUseCase;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\EditorUploads\EditorUploadConsumerInterface;
use Johncms\EditorUploads\EditorUploadConsumers;
use Johncms\EditorUploads\EditorUploadRepositoryInterface;
use Johncms\EditorUploads\EditorUploads;
use Johncms\EditorUploads\ImageSources;
use Johncms\Files\FileStore;
use Johncms\Files\StoredFileDTO;
use Johncms\Http\UploadedFileDTO;
use Johncms\Image\EditorImageUploadException;
use Johncms\Image\StoreEditorImageUseCase;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The editor pictures from the upload to the record, against the real database and disk.
 *
 * The service is built by hand around a consumer of the test: no record of the CMS tracks its
 * pictures through it yet. The transactions run for real, and what waits for the commit runs at
 * once — the suite never commits, it rolls every test back.
 */
final class EditorUploadsTest extends FunctionalTestCase
{
    private const string OWNER = 'test_record';
    private const string DIRECTORY = 'test_editor_uploads';

    /** @var list<int> */
    private array $storedFiles = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    private EditorUploads $uploads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploads = new EditorUploads(
            consumers: $this->consumers(),
            uploads: $this->container()->get(EditorUploadRepositoryInterface::class),
            storeEditorImage: $this->container()->get(StoreEditorImageUseCase::class),
            files: $this->files(),
            transactions: $this->transactions(),
            imageSources: $this->container()->get(ImageSources::class),
        );
    }

    protected function tearDown(): void
    {
        // The rows go away with the transaction, the files on the disk do not.
        $this->files()->deleteMany($this->storedFiles);
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->storedFiles = [];
        $this->temporaryFiles = [];

        parent::tearDown();
    }

    public function testAStoredPictureIsTheUnattachedUploadOfItsUser(): void
    {
        $userId = FunctionalUserFactory::create()->id;

        $file = $this->store($userId);

        $row = Capsule::table('editor_uploads')->where('file_id', $file->id)->first();
        self::assertNotNull($row);
        self::assertSame($userId, (int) $row->user_id);
        self::assertSame(self::DIRECTORY, $row->directory);
        self::assertNull($row->owner_type);
    }

    public function testTheLimitOfUnsentPicturesRefusesTheNextOneWithoutLeavingAnything(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        for ($i = 0; $i < EditorUploads::MAX_UNBOUND_PER_USER; $i++) {
            $this->insertRow(['user_id' => $userId]);
        }
        $files = Capsule::table('files')->count();

        try {
            $this->store($userId);
            self::fail('The upload over the limit must be refused.');
        } catch (EditorImageUploadException) {
        }

        self::assertSame(EditorUploads::MAX_UNBOUND_PER_USER, Capsule::table('editor_uploads')->where('user_id', $userId)->count());
        self::assertSame($files, Capsule::table('files')->count());
    }

    public function testARefusedFileLeavesNoReservation(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $path = $this->temporaryFile('txt');
        file_put_contents($path, 'Not a picture.');

        try {
            $this->uploads->store(new UploadedFileDTO('photo.jpg', 'image/jpeg', (int) filesize($path), $path, UPLOAD_ERR_OK), self::OWNER, $userId);
            self::fail('A file that is not a picture must be refused.');
        } catch (EditorImageUploadException) {
        }

        self::assertSame(0, Capsule::table('editor_uploads')->where('user_id', $userId)->count());
    }

    public function testTheRecordTakesOnlyThePicturesItsTextShows(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $shown = $this->store($userId);
        $linked = $this->store($userId);
        $written = $this->store($userId);

        $this->uploads->bind(self::OWNER, 7, $userId, sprintf(
            '<p><img src="%s"></p><p><a href="%s">a link</a></p><p>%s</p>',
            $shown->url,
            $linked->url,
            $written->url
        ));

        self::assertSame([$shown->id], $this->owned(7));
        self::assertSame([$linked->id, $written->id], $this->unbound($userId));
    }

    public function testAnAbsoluteAddressOfTheSiteAndEncodedEntitiesNameTheSamePicture(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $file = $this->store($userId);

        $this->withConfig(['johncms' => ['homeurl' => 'https://site.test']], function () use ($file, $userId): void {
            $this->uploads->bind(self::OWNER, 7, $userId, '<img src="https://SITE.test' . $file->url . '?size=1&amp;v=2">');
        });

        self::assertSame([$file->id], $this->owned(7));
    }

    public function testAnotherHostIsNotOurFile(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $file = $this->store($userId);

        $this->withConfig(['johncms' => ['homeurl' => 'https://site.test']], function () use ($file, $userId): void {
            $this->uploads->bind(self::OWNER, 7, $userId, '<img src="https://elsewhere.test' . $file->url . '">');
        });

        self::assertSame([], $this->owned(7));
    }

    public function testThePictureOfAnotherUserIsNotTaken(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $stranger = FunctionalUserFactory::create()->id;
        $foreign = $this->store($stranger);

        $this->uploads->bind(self::OWNER, 7, $author, '<img src="' . $foreign->url . '">');

        self::assertSame([], $this->owned(7));
        self::assertSame([$foreign->id], $this->unbound($stranger));
    }

    public function testMoreThanTheLimitOfPicturesInOneTextIsRefused(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $html = '';
        for ($i = 0; $i <= EditorUploads::MAX_PER_OWNER; $i++) {
            $html .= '<img src="' . $this->store($userId)->url . '">';
        }

        $this->expectException(EditorUploadBindException::class);
        $this->uploads->bind(self::OWNER, 7, $userId, $html);
    }

    public function testIdenticalUploadsOfOnePictureCountOnce(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $original = $this->picture();
        $files = [];
        for ($i = 0; $i <= EditorUploads::MAX_PER_OWNER; $i++) {
            // Each upload is a copy of the same bytes: the store gives them one path, so one address.
            $copy = $this->temporaryFile('png');
            copy($original, $copy);
            $files[] = $this->uploads->store(new UploadedFileDTO('photo.png', 'image/png', (int) filesize($copy), $copy, UPLOAD_ERR_OK), self::OWNER, $userId);
        }
        $this->storedFiles = [...$this->storedFiles, ...array_map(static fn(StoredFileDTO $file): int => $file->id, $files)];
        self::assertSame($files[0]->url, $files[EditorUploads::MAX_PER_OWNER]->url);

        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $files[0]->url . '">');
        self::assertCount(1, $this->owned(7));

        // Shown again on an edit, the picture the record holds is the one it keeps.
        $held = $this->owned(7);
        $this->uploads->bind(self::OWNER, 7, $userId, '<p>Edited</p><img src="' . $files[0]->url . '">');
        self::assertSame($held, $this->owned(7));
    }

    public function testAnEditKeepsThePicturesStillShownAndDeletesTheOthers(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $kept = $this->store($userId);
        $dropped = $this->store($userId);
        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $kept->url . '"><img src="' . $dropped->url . '">');

        $this->uploads->bind(self::OWNER, 7, $userId, '<p>Edited</p><img src="' . $kept->url . '">');

        self::assertSame([$kept->id], $this->owned(7));
        self::assertNull($this->files()->find($dropped->id));
    }

    public function testAModeratorKeepsThePicturesOfTheAuthorAndAddsTheirOwn(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $moderator = FunctionalUserFactory::create()->id;
        $ofAuthor = $this->store($author);
        $this->uploads->bind(self::OWNER, 7, $author, '<img src="' . $ofAuthor->url . '">');
        $ofModerator = $this->store($moderator);

        $this->uploads->bind(self::OWNER, 7, $moderator, '<img src="' . $ofAuthor->url . '"><img src="' . $ofModerator->url . '">');

        self::assertSame([$ofAuthor->id, $ofModerator->id], $this->owned(7));
    }

    public function testAPictureAlreadyTakenByAnotherRecordCannotBeTakenAgain(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $file = $this->store($userId);
        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $file->url . '">');

        // Another form of the same user showing the same picture: it is not theirs to give any more.
        $this->uploads->bind(self::OWNER, 8, $userId, '<img src="' . $file->url . '">');

        self::assertSame([$file->id], $this->owned(7));
        self::assertSame([], $this->owned(8));
        self::assertSame(
            0,
            $this->container()->get(EditorUploadRepositoryInterface::class)->bind([$file->id], self::OWNER, 8, $userId)
        );
    }

    public function testDetachingRecordsDeletesTheirPicturesOnly(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $deleted = $this->store($userId);
        $other = $this->store($userId);
        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $deleted->url . '">');
        $this->uploads->bind(self::OWNER, 8, $userId, '<img src="' . $other->url . '">');

        $this->uploads->detach(self::OWNER, [7]);

        self::assertSame([], $this->owned(7));
        self::assertNull($this->files()->find($deleted->id));
        self::assertNotNull($this->files()->find($other->id));
    }

    public function testDetachingAUserDeletesWhatTheyNeverSent(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $sent = $this->store($userId);
        $unsent = $this->store($userId);
        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $sent->url . '">');

        $this->uploads->detachUser($userId);

        self::assertSame([], $this->unbound($userId));
        self::assertNull($this->files()->find($unsent->id));
        self::assertSame([$sent->id], $this->owned(7));
    }

    public function testFilesWaitForTheCommitOfTheDeletion(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $file = $this->store($userId);
        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $file->url . '">');

        $deferred = [];
        $uploads = new EditorUploads(
            consumers: $this->consumers(),
            uploads: $this->container()->get(EditorUploadRepositoryInterface::class),
            storeEditorImage: $this->container()->get(StoreEditorImageUseCase::class),
            files: $this->files(),
            transactions: $this->transactions($deferred),
            imageSources: $this->container()->get(ImageSources::class),
        );
        $uploads->detach(self::OWNER, [7]);

        self::assertNotNull($this->files()->find($file->id), 'The file must stay until the deletion commits.');
        self::assertCount(1, $deferred);
    }

    public function testTheCleanupRemovesAbandonedAndUntrackedFilesOfTheDeclaredDirectoriesOnly(): void
    {
        $userId = FunctionalUserFactory::create()->id;
        $abandoned = $this->store($userId);
        $fresh = $this->store($userId);
        $sent = $this->store($userId);
        $this->uploads->bind(self::OWNER, 7, $userId, '<img src="' . $sent->url . '">');
        $old = (new DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s');
        Capsule::table('editor_uploads')->whereIn('file_id', [$abandoned->id, $sent->id])->update(['created_at' => $old]);
        $reservation = $this->insertRow(['user_id' => $userId, 'created_at' => $old]);

        $untracked = $this->storeContents(self::DIRECTORY);
        $elsewhere = $this->storeContents('test_editor_uploads_undeclared');
        Capsule::table('files')->whereIn('id', [$untracked->id, $elsewhere->id, $sent->id])->update(['created_at' => $old]);

        $result = new CleanupEditorUploadsUseCase(
            consumers: $this->consumers(),
            uploads: $this->container()->get(EditorUploadRepositoryInterface::class),
            transactions: $this->transactions(),
            files: $this->files(),
        )->execute();

        // The reservation had no file yet: it is removed, but only the abandoned picture counts.
        self::assertSame(['abandoned' => 1, 'untracked' => 1], $result);
        self::assertNull($this->files()->find($abandoned->id));
        self::assertNull($this->files()->find($untracked->id));
        self::assertFalse(Capsule::table('editor_uploads')->where('id', $reservation)->exists());
        self::assertNotNull($this->files()->find($fresh->id));
        self::assertNotNull($this->files()->find($sent->id));
        self::assertNotNull($this->files()->find($elsewhere->id));
    }

    public function testTheSiteDeclaresNoConsumerYetSoTheCleanupTouchesNothing(): void
    {
        $old = (new DateTimeImmutable('-2 days'))->format('Y-m-d H:i:s');
        $guestbook = $this->storeContents('guestbook');
        Capsule::table('files')->where('id', $guestbook->id)->update(['created_at' => $old]);

        $result = $this->container()->get(CleanupEditorUploadsUseCase::class)->execute();

        self::assertSame(['abandoned' => 0, 'untracked' => 0], $result);
        self::assertNotNull($this->files()->find($guestbook->id));
    }

    private function store(int $userId): StoredFileDTO
    {
        $path = $this->picture();
        $file = $this->uploads->store(new UploadedFileDTO('photo.png', 'image/png', (int) filesize($path), $path, UPLOAD_ERR_OK), self::OWNER, $userId);
        $this->storedFiles[] = $file->id;

        return $file;
    }

    private function storeContents(string $directory): StoredFileDTO
    {
        $file = $this->files()->storeContents('contents ' . bin2hex(random_bytes(8)), 'file.txt', $directory);
        $this->storedFiles[] = $file->id;

        return $file;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function insertRow(array $attributes): int
    {
        return Fixture::insert('editor_uploads', $attributes + [
            'directory'  => self::DIRECTORY,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<int> */
    private function owned(int $ownerId): array
    {
        return array_map('intval', Capsule::table('editor_uploads')
            ->where('owner_type', self::OWNER)
            ->where('owner_id', $ownerId)
            ->orderBy('file_id')
            ->pluck('file_id')
            ->all());
    }

    /** @return list<int> */
    private function unbound(int $userId): array
    {
        return array_map('intval', Capsule::table('editor_uploads')
            ->where('user_id', $userId)
            ->whereNull('owner_type')
            ->orderBy('file_id')
            ->pluck('file_id')
            ->all());
    }

    private function consumers(): EditorUploadConsumers
    {
        return new EditorUploadConsumers([
            new class (self::OWNER, self::DIRECTORY) implements EditorUploadConsumerInterface {
                public function __construct(private string $ownerType, private string $directory)
                {
                }

                public function ownerType(): string
                {
                    return $this->ownerType;
                }

                public function directory(): string
                {
                    return $this->directory;
                }
            },
        ]);
    }

    /**
     * Real transactions, and what waits for the commit either runs at once or is collected.
     *
     * @param list<callable>|null $deferred
     */
    private function transactions(?array &$deferred = null): TransactionManagerInterface
    {
        return new class ($deferred) implements TransactionManagerInterface {
            /** @param list<callable>|null $deferred */
            public function __construct(private ?array &$deferred)
            {
            }

            public function run(callable $operation): mixed
            {
                return Capsule::connection()->transaction($operation);
            }

            public function afterCommit(callable $callback): void
            {
                if ($this->deferred === null) {
                    $callback();

                    return;
                }

                $this->deferred[] = $callback;
            }
        };
    }

    private function files(): FileStore
    {
        return $this->container()->get(FileStore::class);
    }

    private function picture(): string
    {
        $image = imagecreatetruecolor(4, 4);
        // A colour of its own for every picture: identical contents would share one path.
        imagefill($image, 0, 0, (int) imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        imagesetpixel($image, random_int(0, 3), random_int(0, 3), (int) imagecolorallocate($image, random_int(0, 255), 0, 0));
        $path = $this->temporaryFile('png');
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private function temporaryFile(string $extension): string
    {
        $path = sys_get_temp_dir() . '/johncms-editor-upload-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $this->temporaryFiles[] = $path;

        return $path;
    }
}
