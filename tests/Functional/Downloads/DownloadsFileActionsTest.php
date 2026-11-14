<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Voting for a file and keeping it in the favourites are forms of its page; a link changes nothing.
 */
final class DownloadsFileActionsTest extends FunctionalTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
        file_put_contents($this->directory . '/file.zip', 'a download');
    }

    protected function tearDown(): void
    {
        unlink($this->directory . '/file.zip');
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testAVoteIsCountedOnlyFromTheFormAndOnce(): void
    {
        $user = FunctionalUserFactory::create();
        $id = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $this->directory, 'rus_name' => 'A file', 'slug' => 'a-file', 'rate' => '0|0']);
        $url = '/downloads/a-file-' . $id . '/';
        $cookies = $this->actingAs($user);

        $this->handleRequest($url . '?plus', cookies: $cookies);
        self::assertSame('0|0', DownloadFile::query()->whereKey($id)->value('rate'), 'Opening a link does not vote.');

        $response = $this->handleRequest($url, 'POST', $this->withCsrfToken(['action' => 'plus']), cookies: $cookies);
        self::assertSame(302, $response->getStatusCode(), 'The vote leads back to the page, so a reload does not send it again.');
        self::assertSame($url, $response->headers->get('Location'));
        self::assertSame('1|0', DownloadFile::query()->whereKey($id)->value('rate'));

        $this->handleRequest($url, 'POST', $this->withCsrfToken(['action' => 'minus']), cookies: $cookies);
        self::assertSame('1|0', DownloadFile::query()->whereKey($id)->value('rate'), 'One vote per session.');
    }

    public function testTheFavouritesChangeOnlyFromTheForm(): void
    {
        $user = FunctionalUserFactory::create();
        $id = Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $this->directory, 'rus_name' => 'A file', 'slug' => 'a-file']);
        $url = '/downloads/a-file-' . $id . '/';
        $bookmarks = static fn (): int => Capsule::table('download__bookmark')->where('file_id', $id)->where('user_id', $user->id)->count();

        $this->handleRequest($url . '?addBookmark', cookies: $this->actingAs($user));
        self::assertSame(0, $bookmarks(), 'Opening a link does not add the file.');

        $this->postForm($url, ['action' => 'addBookmark'], $user);
        self::assertSame(1, $bookmarks());

        $this->postForm($url, ['action' => 'delBookmark'], $user);
        self::assertSame(0, $bookmarks());
    }
}
