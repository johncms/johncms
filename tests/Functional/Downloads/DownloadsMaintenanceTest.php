<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Dom\HTMLDocument;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The maintenance of the downloads — the scan, the removal of missing files, the recount, moving
 * and accepting files, the order of categories — is started by a form; opening an address changes
 * nothing.
 */
final class DownloadsMaintenanceTest extends FunctionalTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
        file_put_contents($this->directory . '/present.zip', 'a download');
    }

    protected function tearDown(): void
    {
        unlink($this->directory . '/present.zip');
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testMissingFilesAreRemovedOnlyByAConfirmedForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        // type 2 is a file of the catalogue.
        $present = Fixture::insert('download__files', ['type' => 2, 'name' => 'present.zip', 'dir' => $this->directory, 'rus_name' => 'Present']);
        $missing = Fixture::insert('download__files', ['type' => 2, 'name' => 'missing.zip', 'dir' => $this->directory, 'rus_name' => 'Missing']);

        $this->handleRequest('/downloads/scan-dir?do=clean', cookies: $this->actingAs($moderator));
        self::assertTrue(DownloadFile::query()->whereKey($missing)->exists(), 'Opening the old address removes nothing.');

        $unconfirmed = $this->postForm('/downloads/scan-dir/clean', ['id' => '0'], $moderator);
        self::assertSame(409, $unconfirmed->getStatusCode(), 'Without the answer of the dialog nothing is removed.');
        self::assertTrue(DownloadFile::query()->whereKey($missing)->exists());

        $cleaned = $this->postForm('/downloads/scan-dir/clean', ['id' => '0', 'confirmed' => '1'], $moderator);
        self::assertSame(200, $cleaned->getStatusCode());
        self::assertFalse(DownloadFile::query()->whereKey($missing)->exists());
        self::assertTrue(DownloadFile::query()->whereKey($present)->exists());
    }

    public function testCountersAreRecountedOnlyByAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $category = Fixture::insert('download__category', ['name' => 'category', 'dir' => $this->directory, 'rus_name' => 'Category', 'total' => 0]);
        Fixture::insert('download__files', ['type' => 2, 'refid' => $category, 'name' => 'present.zip', 'dir' => $this->directory, 'rus_name' => 'Present']);

        $this->handleRequest('/downloads/recount?id=' . $category, cookies: $this->actingAs($moderator));
        self::assertSame(0, DownloadCategory::query()->whereKey($category)->value('total'), 'Opening the old address counts nothing.');

        $recounted = $this->postForm('/downloads/recount', ['id' => (string) $category], $moderator);
        self::assertSame(302, $recounted->getStatusCode());
        self::assertSame(1, DownloadCategory::query()->whereKey($category)->value('total'));
    }

    public function testTheScanStartsOnlyFromAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();

        $page = HTMLDocument::createFromString(
            (string) $this->handleRequest('/downloads/scan-dir?yes', cookies: $this->actingAs($moderator))->getContent(),
            LIBXML_NOERROR,
        );

        self::assertNull($page->querySelector('.alert-info'), 'Opening the old address does not scan.');
        self::assertNotNull($page->querySelector('form[method="post"][action="/downloads/scan-dir"] input[name="mod"]'));
    }

    public function testAFileIsMovedOnlyByAConfirmedForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $target = $this->directory . '/target';
        mkdir($target);
        file_put_contents($this->directory . '/moved.zip', 'a download');
        $from = Fixture::insert('download__category', ['name' => 'from', 'dir' => $this->directory, 'rus_name' => 'From', 'total' => 0]);
        $to = Fixture::insert('download__category', ['name' => 'target', 'dir' => $target, 'rus_name' => 'Target', 'total' => 0]);
        $file = Fixture::insert('download__files', ['type' => 2, 'refid' => $from, 'name' => 'moved.zip', 'dir' => $this->directory, 'rus_name' => 'Moved']);

        try {
            $this->handleRequest('/downloads/move-file/' . $file . '/?catId=' . $to . '&do=transfer&yes', cookies: $this->actingAs($moderator));
            self::assertSame($from, DownloadFile::query()->whereKey($file)->value('refid'), 'Opening the old address moves nothing.');

            $unconfirmed = $this->postForm('/downloads/move-file/' . $file, ['catId' => (string) $to], $moderator);
            self::assertSame(409, $unconfirmed->getStatusCode());
            self::assertSame($from, DownloadFile::query()->whereKey($file)->value('refid'));

            $moved = $this->postForm('/downloads/move-file/' . $file, ['catId' => (string) $to, 'confirmed' => '1'], $moderator);
            self::assertSame(200, $moved->getStatusCode());
            self::assertSame($to, DownloadFile::query()->whereKey($file)->value('refid'));
            self::assertFileExists($target . '/moved.zip');
            self::assertSame(1, DownloadCategory::query()->whereKey($to)->value('total'), 'The counters follow the move.');
        } finally {
            @unlink($target . '/moved.zip');
            @unlink($this->directory . '/moved.zip');
            rmdir($target);
        }
    }

    public function testFilesAreAcceptedOnlyByAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        // type 3 waits for a moderator.
        $first = Fixture::insert('download__files', ['type' => 3, 'name' => 'present.zip', 'dir' => $this->directory, 'rus_name' => 'First']);
        $second = Fixture::insert('download__files', ['type' => 3, 'name' => 'present.zip', 'dir' => $this->directory, 'rus_name' => 'Second']);

        $this->handleRequest('/downloads/moderation?accept=' . $first, cookies: $this->actingAs($moderator));
        self::assertSame(3, DownloadFile::query()->whereKey($first)->value('type'), 'Opening the old address accepts nothing.');

        $this->postForm('/downloads/moderation', ['accept' => (string) $first], $moderator);
        self::assertSame(2, DownloadFile::query()->whereKey($first)->value('type'));
        self::assertSame(3, DownloadFile::query()->whereKey($second)->value('type'));

        self::assertSame(409, $this->postForm('/downloads/moderation/accept-all', [], $moderator)->getStatusCode());
        self::assertSame(3, DownloadFile::query()->whereKey($second)->value('type'));
        $this->postForm('/downloads/moderation/accept-all', ['confirmed' => '1'], $moderator);
        self::assertSame(2, DownloadFile::query()->whereKey($second)->value('type'));
    }

    public function testCategoriesAreReorderedOnlyByAForm(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $upper = Fixture::insert('download__category', ['name' => 'upper', 'dir' => $this->directory, 'rus_name' => 'Upper', 'sort' => 1]);
        $lower = Fixture::insert('download__category', ['name' => 'lower', 'dir' => $this->directory, 'rus_name' => 'Lower', 'sort' => 2]);

        $this->handleRequest('/downloads/categories/' . $lower . '/edit?do=up', cookies: $this->actingAs($moderator));
        self::assertSame(2, DownloadCategory::query()->whereKey($lower)->value('sort'), 'Opening the old address reorders nothing.');

        $response = $this->postForm('/downloads/categories/' . $lower . '/sort', ['direction' => 'up'], $moderator);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame(1, DownloadCategory::query()->whereKey($lower)->value('sort'));
        self::assertSame(2, DownloadCategory::query()->whereKey($upper)->value('sort'));
    }
}
