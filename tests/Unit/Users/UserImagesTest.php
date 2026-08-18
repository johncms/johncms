<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use Johncms\Storage\FlysystemStorage;
use Johncms\Storage\StorageInterface;
use Johncms\Users\UserImages;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;

/**
 * The names of the two pictures a user has used to be spelled out wherever they were needed.
 * These tests pin them down, because a rename here is an avatar that uploads and never appears.
 */
final class UserImagesTest extends TestCase
{
    private StorageInterface $disk;
    private UserImages $images;

    protected function setUp(): void
    {
        $this->disk = new FlysystemStorage(
            filesystem: new Filesystem(new InMemoryFilesystemAdapter()),
            baseUrl: '/upload',
        );
        $this->images = new UserImages($this->disk);
    }

    public function testStoresTheAvatarWhereTheThemeLooksForIt(): void
    {
        $this->images->storeAvatar(5, static function (string $target): void {
            file_put_contents($target, 'png bytes');
        });

        self::assertSame('png bytes', $this->disk->read('users/avatar/5.png'));
        self::assertTrue($this->images->hasAvatar(5));
    }

    public function testTheAvatarUrlCarriesTheTimeItWasWritten(): void
    {
        $this->disk->store('users/avatar/5.png', 'png bytes');

        self::assertMatchesRegularExpression('#^/upload/users/avatar/5\.png\?v=\d+$#', $this->images->avatarUrl(5));
    }

    public function testWithoutAnAvatarThereIsNoUrl(): void
    {
        self::assertSame('', $this->images->avatarUrl(5));
        self::assertFalse($this->images->hasAvatar(5));
    }

    /**
     * Messages of deleted users are shown with a zero id; asking the disk about it would be a
     * lookup per message on every page of a topic.
     */
    public function testAGuestHasNoAvatar(): void
    {
        self::assertFalse($this->images->hasAvatar(0));
        self::assertSame('', $this->images->avatarUrl(0));
    }

    public function testDeletingAnAvatarThatIsNotThereIsHarmless(): void
    {
        $this->images->deleteAvatar(5);

        self::assertFalse($this->images->hasAvatar(5));
    }

    public function testStoresBothSizesOfThePhoto(): void
    {
        $this->images->storePhoto(
            7,
            static function (string $target): void {
                file_put_contents($target, 'full size');
            },
            static function (string $target): void {
                file_put_contents($target, 'preview');
            },
        );

        self::assertSame('full size', $this->disk->read('users/photo/7.jpg'));
        self::assertSame('preview', $this->disk->read('users/photo/7_small.jpg'));
        self::assertTrue($this->images->hasPhoto(7));
        self::assertSame('/upload/users/photo/7.jpg', $this->images->photoUrl(7));
        self::assertSame('/upload/users/photo/7_small.jpg', $this->images->photoPreviewUrl(7));
    }

    /**
     * The preview is what the user model checks for, because it is the one every listing shows.
     */
    public function testAPhotoWithoutItsPreviewDoesNotCount(): void
    {
        $this->disk->store('users/photo/7.jpg', 'full size');

        self::assertFalse($this->images->hasPhoto(7));
    }

    public function testDeletingThePhotoRemovesBothSizes(): void
    {
        $this->disk->store('users/photo/7.jpg', 'full size');
        $this->disk->store('users/photo/7_small.jpg', 'preview');

        $this->images->deletePhoto(7);

        self::assertFalse($this->disk->exists('users/photo/7.jpg'));
        self::assertFalse($this->disk->exists('users/photo/7_small.jpg'));
    }
}
