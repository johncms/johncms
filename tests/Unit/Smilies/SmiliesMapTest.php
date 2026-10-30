<?php

declare(strict_types=1);

namespace Tests\Unit\Smilies;

use Johncms\Smilies\SmileyFolders;
use Johncms\Smilies\SmiliesMap;
use Johncms\Smilies\SmiliesRenderer;
use PHPUnit\Framework\TestCase;

final class SmiliesMapTest extends TestCase
{
    private string $root;
    private string $cache;

    protected function setUp(): void
    {
        $base = sys_get_temp_dir() . '/smilies-' . bin2hex(random_bytes(4));
        $this->root = $base . '/emoticons';
        $this->cache = $base . '/cache/';
        foreach (['simply', 'admin', 'user/animals'] as $folder) {
            mkdir($this->root . '/' . $folder, 0777, true);
        }
        mkdir($this->cache);

        touch($this->root . '/simply/smile.gif');
        touch($this->root . '/admin/ban.png');
        touch($this->root . '/user/animals/kot.gif');
        touch($this->root . '/user/animals/readme.txt');
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg(dirname($this->root)));
    }

    public function testTheMapIsBuiltFromTheFoldersWithoutTheAddressOfTheSite(): void
    {
        $map = $this->map()->get();

        self::assertSame('assets/emoticons/simply/smile.gif', $map['usr'][':smile']);
        self::assertSame('assets/emoticons/user/animals/kot.gif', $map['usr'][':kot:']);
        self::assertSame('assets/emoticons/user/animals/kot.gif', $map['usr'][':кот:']);
        self::assertSame('assets/emoticons/admin/ban.png', $map['adm'][':ban:']);
        self::assertArrayNotHasKey(':ban:', $map['usr']);
        self::assertFileExists($this->cache . SmiliesMap::CACHE_FILE);
    }

    public function testAPicturePutInAFolderIsPickedUpWithoutARebuild(): void
    {
        $this->map()->get();

        touch($this->root . '/user/animals/pes.gif');
        // The folder is newer than the cache, as it is a moment after the file was written.
        touch($this->root . '/user/animals', time() + 5);

        self::assertArrayHasKey(':pes:', $this->map()->get()['usr']);
    }

    public function testACacheOfTheOldFormatIsBuiltAgain(): void
    {
        file_put_contents($this->cache . SmiliesMap::CACHE_FILE, serialize([
            'usr' => [':smile' => '<img src="http://old.example/assets/emoticons/simply/smile.gif" alt="" />'],
            'adm' => [],
        ]));
        touch($this->cache . SmiliesMap::CACHE_FILE, time() + 5);

        self::assertSame('assets/emoticons/simply/smile.gif', $this->map()->get()['usr'][':smile']);
    }

    public function testTheRendererAddsTheAddressOfTheSite(): void
    {
        $html = (new SmiliesRenderer($this->map()))->render('hi :smile');

        self::assertSame('hi <img src="' . rtrim((string) (config('johncms')['homeurl'] ?? ''), '/') . '/assets/emoticons/simply/smile.gif" alt="" />', $html);
    }

    private function map(): SmiliesMap
    {
        return new SmiliesMap(new SmileyFolders($this->root), $this->cache);
    }
}
