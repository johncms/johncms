<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use PHPUnit\Framework\TestCase;

/**
 * The state file is the only record of what a site has installed, and it is read before anything
 * else exists — no container, no database. It has to survive being absent, being empty and being
 * edited by hand, and what it writes has to be readable back.
 */
final class ModuleStateStoreTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . DS . 'johncms-state-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testAMissingFileMeansNothingIsRecorded(): void
    {
        self::assertSame([], (new ModuleStateStore($this->file))->all());
    }

    public function testAFileThatIsNotAStateFileIsIgnored(): void
    {
        file_put_contents($this->file, '<?php return "nonsense";');

        self::assertSame([], (new ModuleStateStore($this->file))->all());
    }

    public function testWhatIsWrittenIsReadBack(): void
    {
        $store = new ModuleStateStore($this->file);
        $store->save([
            'vasya/blog'   => new ModuleStateRecord('vasya/blog', 'blog', version: '1.2.0', installedAt: 1755000000),
            'johncms/news' => new ModuleStateRecord('johncms/news', 'news', enabled: false),
        ]);

        $records = (new ModuleStateStore($this->file))->all();

        self::assertSame(['johncms/news', 'vasya/blog'], array_keys($records), 'Records are kept in order.');
        self::assertSame('blog', $records['vasya/blog']->alias);
        self::assertSame('1.2.0', $records['vasya/blog']->version);
        self::assertSame(1755000000, $records['vasya/blog']->installedAt);
        self::assertTrue($records['vasya/blog']->enabled);
        self::assertFalse($records['johncms/news']->enabled);
        self::assertNull($records['johncms/news']->version);
    }

    /**
     * The file is generated, but it is also the thing an administrator opens over FTP when a
     * module has taken the site down — so it has to look like the rest of the configuration.
     */
    public function testTheGeneratedFileIsOrdinaryReadablePhp(): void
    {
        (new ModuleStateStore($this->file))->save([
            'vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog'),
        ]);

        $contents = (string) file_get_contents($this->file);

        self::assertStringStartsWith('<?php', $contents);
        self::assertStringContainsString('declare(strict_types=1);', $contents);
        self::assertStringContainsString("            'vasya/blog' => [", $contents);
        self::assertStringNotContainsString('NULL', $contents, 'var_export writes NULL in capitals.');
    }

    public function testSavingReplacesWhatWasThere(): void
    {
        $store = new ModuleStateStore($this->file);
        $store->save(['vasya/blog' => new ModuleStateRecord('vasya/blog', 'blog')]);
        $store->save(['johncms/news' => new ModuleStateRecord('johncms/news', 'news')]);

        self::assertSame(['johncms/news'], array_keys((new ModuleStateStore($this->file))->all()));
    }

    public function testARecordWithoutAnAliasFallsBackToTheKey(): void
    {
        file_put_contents(
            $this->file,
            '<?php return ' . var_export(['modules' => ['state' => ['vasya/blog' => ['installed' => true]]]], true) . ';'
        );

        self::assertSame('vasya.blog', (new ModuleStateStore($this->file))->all()['vasya/blog']->alias);
    }
}
