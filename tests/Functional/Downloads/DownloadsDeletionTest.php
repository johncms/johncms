<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * A file, its additional files and its screenshots are deleted by a form that asks first; without
 * the answer of the dialog, or by opening an address, nothing is deleted.
 */
final class DownloadsDeletionTest extends FunctionalTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
        file_put_contents($this->directory . '/file.zip', 'a download');
        file_put_contents($this->directory . '/more.zip', 'an attachment');
    }

    protected function tearDown(): void
    {
        @unlink($this->directory . '/file.zip');
        @unlink($this->directory . '/more.zip');
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testAFileIsDeletedOnlyByAConfirmedForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $id = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $this->directory, 'rus_name' => 'A file']);

        $this->handleRequest('/downloads/delete-file/' . $id . '/', cookies: $this->actingAs($moderator));
        self::assertTrue(DownloadFile::query()->whereKey($id)->exists(), 'Opening the address deletes nothing.');

        self::assertSame(409, $this->postForm('/downloads/delete-file/' . $id, [], $moderator)->getStatusCode());
        self::assertTrue(DownloadFile::query()->whereKey($id)->exists());

        $deleted = $this->postForm('/downloads/delete-file/' . $id, ['confirmed' => '1'], $moderator);
        self::assertSame(302, $deleted->getStatusCode());
        self::assertFalse(DownloadFile::query()->whereKey($id)->exists());
        self::assertFileDoesNotExist($this->directory . '/file.zip');
    }

    public function testAnAdditionalFileIsDeletedOnlyFromItsOwnFile(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $id = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $this->directory, 'rus_name' => 'A file']);
        $other = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $this->directory, 'rus_name' => 'Another file']);
        $more = Fixture::insert('download__more', ['refid' => $id, 'name' => 'more.zip', 'rus_name' => 'Attachment', 'time' => time(), 'size' => 13]);

        $this->handleRequest('/downloads/additional-files/' . $id . '/?del=' . $more . '&yes', cookies: $this->actingAs($moderator));
        self::assertTrue(DownloadMoreFile::query()->whereKey($more)->exists(), 'Opening the address deletes nothing.');

        self::assertSame(409, $this->postForm('/downloads/additional-files/' . $id . '/delete', ['more_id' => (string) $more], $moderator)->getStatusCode());

        $this->postForm('/downloads/additional-files/' . $other . '/delete', ['more_id' => (string) $more, 'confirmed' => '1'], $moderator);
        self::assertTrue(DownloadMoreFile::query()->whereKey($more)->exists(), 'An attachment of another file is not reached.');

        $this->postForm('/downloads/additional-files/' . $id . '/delete', ['more_id' => (string) $more, 'confirmed' => '1'], $moderator);
        self::assertFalse(DownloadMoreFile::query()->whereKey($more)->exists());
        self::assertFileDoesNotExist($this->directory . '/more.zip');
    }

    public function testAScreenshotIsDeletedOnlyByAConfirmedFormAndOnlyInItsFolder(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $id = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $this->directory, 'rus_name' => 'A file']);
        $screens = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id;
        mkdir($screens, 0777, true);
        file_put_contents($screens . '/shot.png', 'a picture');

        try {
            self::assertSame(409, $this->postForm('/downloads/edit-screen/' . $id . '/delete', ['screen' => 'shot.png'], $moderator)->getStatusCode());
            self::assertFileExists($screens . '/shot.png');

            $this->postForm('/downloads/edit-screen/' . $id . '/delete', ['screen' => '../../../../file.zip', 'confirmed' => '1'], $moderator);
            self::assertFileExists($this->directory . '/file.zip');

            $this->postForm('/downloads/edit-screen/' . $id . '/delete', ['screen' => 'shot.png', 'confirmed' => '1'], $moderator);
            self::assertFileDoesNotExist($screens . '/shot.png');
        } finally {
            @unlink($screens . '/shot.png');
            @rmdir($screens);
        }
    }
}
