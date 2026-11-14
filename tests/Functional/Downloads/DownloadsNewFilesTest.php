<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

/**
 * The new files of a category (/downloads/new/?id=…) are those of that category only.
 */
final class DownloadsNewFilesTest extends FunctionalTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($this->directory . '/inside', 0777, true);
        mkdir($this->directory . '/outside');
    }

    protected function tearDown(): void
    {
        rmdir($this->directory . '/inside');
        rmdir($this->directory . '/outside');
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testTheNewFilesOfACategoryAreThoseOfThatCategory(): void
    {
        $category = Fixture::insert('download__category', ['name' => 'inside', 'slug' => 'inside', 'dir' => $this->directory . '/inside', 'rus_name' => 'Inside']);
        Fixture::insert('download__category', ['name' => 'outside', 'slug' => 'outside', 'dir' => $this->directory . '/outside', 'rus_name' => 'Outside']);
        Fixture::insert('download__files', ['type' => 2, 'refid' => $category, 'name' => 'a.zip', 'dir' => $this->directory . '/inside', 'rus_name' => 'Fresh inside', 'time' => time()]);
        Fixture::insert('download__files', ['type' => 2, 'name' => 'b.zip', 'dir' => $this->directory . '/outside', 'rus_name' => 'Fresh outside', 'time' => time()]);

        $list = (string) $this->handleRequest('/downloads/new/?id=' . $category)->getContent();
        self::assertStringContainsString('Fresh inside', $list);
        self::assertStringNotContainsString('Fresh outside', $list);
    }
}
